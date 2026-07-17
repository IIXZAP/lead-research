"""Real SerpApi integration (Requirement.md §6).

Search strategy: Google Maps (`engine=google_maps`) is the primary
source since it gives phone/rating/address directly. If Maps returns
nothing at all for a query, we fall back to organic web search
(`engine=google`) so the campaign still surfaces a company name and
website to manually verify — organic results never claim a phone or
rating they don't have.

Every request (cached or not) is recorded in `self.usage_logs` for the
caller to hand to the callback payload; cached hits cost no quota and
report `credit_used=0`.

Note: this was built against SerpApi's documented Google Maps/organic
response shape from training knowledge, not verified against a live
key (this sandbox has no network path to serpapi.com and no key to
test with). Confirm field names against a real response before
production use — see tests/test_serpapi_provider.py for the exact
shape this code expects.
"""
from __future__ import annotations

import re
import time
from datetime import datetime, timezone
from typing import Any, Optional

import httpx

from app.core.logging import get_logger
from app.providers.base import SearchProvider
from app.schemas.business import BusinessDetail, BusinessResult, SearchCriteria
from app.schemas.usage import UsageLogEntry
from app.services.quota_tracker import QuotaExceededError, QuotaTracker
from app.services.query_builder import build_queries
from app.services.serpapi_cache import SerpApiCache, compute_request_hash

logger = get_logger(__name__)

RESULTS_PER_PAGE = 20
ORGANIC_FALLBACK_MIN_RESULTS = 3

_API_KEY_PATTERN = re.compile(r"([?&]api_key=)[^&\s]+", re.IGNORECASE)


def _redact_api_key(text: str) -> str:
    """httpx exception messages embed the full request URL, including
    ?api_key=... — this must never reach logs or the api_usage_logs
    table verbatim (Requirement.md §15: no secrets in logs)."""
    return _API_KEY_PATTERN.sub(r"\1***REDACTED***", text)


class SerpApiGoogleMapsProvider(SearchProvider):
    def __init__(
        self,
        *,
        api_key: str,
        base_url: str,
        cache: SerpApiCache,
        quota: QuotaTracker,
        timeout_seconds: int = 30,
        max_retries: int = 3,
        client: httpx.Client | None = None,
    ) -> None:
        self._api_key = api_key
        self._base_url = base_url
        self._cache = cache
        self._quota = quota
        self._max_retries = max_retries
        self._owns_client = client is None
        self._client = client or httpx.Client(timeout=httpx.Timeout(timeout_seconds))
        self.usage_logs: list[UsageLogEntry] = []

    def close(self) -> None:
        if self._owns_client:
            self._client.close()

    def search_businesses(self, criteria: SearchCriteria) -> list[BusinessResult]:
        results: list[BusinessResult] = []
        seen_place_ids: set[str] = set()

        for query in build_queries(criteria):
            if len(results) >= criteria.maximum_leads:
                break

            page_results = self._search_maps_all_pages(query, criteria, seen_place_ids)
            results.extend(page_results)

        if len(results) < min(ORGANIC_FALLBACK_MIN_RESULTS, criteria.maximum_leads):
            results.extend(
                self._search_organic_fallback(criteria, seen_place_ids, criteria.maximum_leads - len(results))
            )

        return results[: criteria.maximum_leads]

    def get_business_detail(self, place_reference: str) -> BusinessDetail:
        params = {
            "engine": "google_maps",
            "data_id": place_reference,
            "api_key": self._api_key,
        }
        data = self._request(params, endpoint="google_maps_detail")
        place = data.get("place_results")

        if not place:
            raise LookupError(f"No SerpApi place_results found for data_id {place_reference!r}")

        return BusinessDetail(**self._parse_maps_entry(place))

    # -- Google Maps search -------------------------------------------------

    def _search_maps_all_pages(
        self, query: str, criteria: SearchCriteria, seen_place_ids: set[str]
    ) -> list[BusinessResult]:
        collected: list[BusinessResult] = []
        start = 0

        while len(collected) < criteria.maximum_leads:
            params = self._maps_params(query, criteria, start)

            try:
                data = self._request(params, endpoint="google_maps")
            except QuotaExceededError:
                logger.error("SerpApi quota exhausted mid-campaign", extra={"context": {"query": query}})
                break

            local_results = data.get("local_results", [])
            if not local_results:
                break

            for entry in local_results:
                business = self._parse_maps_entry(entry)
                place_id = business.get("source_place_id")
                if place_id and place_id in seen_place_ids:
                    continue
                if place_id:
                    seen_place_ids.add(place_id)
                collected.append(BusinessResult(**business))

            if len(local_results) < RESULTS_PER_PAGE:
                break

            start += RESULTS_PER_PAGE

        return collected

    def _maps_params(self, query: str, criteria: SearchCriteria, start: int) -> dict[str, Any]:
        params: dict[str, Any] = {
            "engine": "google_maps",
            "q": query,
            "type": "search",
            "hl": criteria.search_language,
            "gl": criteria.country,
            "start": start,
            "api_key": self._api_key,
        }

        if criteria.latitude is not None and criteria.longitude is not None:
            zoom = self._radius_to_zoom(criteria.radius_km)
            params["ll"] = f"@{criteria.latitude},{criteria.longitude},{zoom}z"

        return params

    @staticmethod
    def _radius_to_zoom(radius_km: int) -> int:
        # Rough radius -> Google Maps zoom mapping; smaller radius = more zoom.
        if radius_km <= 2:
            return 15
        if radius_km <= 5:
            return 13
        if radius_km <= 15:
            return 11
        if radius_km <= 30:
            return 10
        return 8

    @staticmethod
    def _parse_maps_entry(entry: dict[str, Any]) -> dict[str, Any]:
        gps = entry.get("gps_coordinates") or {}

        return {
            "company_name": entry.get("title", "Unknown"),
            "phone": entry.get("phone"),
            "website_url": entry.get("website"),
            "address": entry.get("address"),
            "province": None,
            "district": None,
            "business_type": entry.get("type"),
            "rating": entry.get("rating"),
            "review_count": entry.get("reviews"),
            "latitude": gps.get("latitude"),
            "longitude": gps.get("longitude"),
            "source": "serpapi_google_maps",
            "source_place_id": entry.get("place_id") or entry.get("data_id"),
            "raw_source_data": entry,
        }

    # -- Organic fallback -----------------------------------------------------

    def _search_organic_fallback(
        self, criteria: SearchCriteria, seen_place_ids: set[str], remaining: int
    ) -> list[BusinessResult]:
        if remaining <= 0:
            return []

        query = f"{criteria.business_keyword} {criteria.province or criteria.location_text or ''}".strip()
        params = {
            "engine": "google",
            "q": query,
            "hl": criteria.search_language,
            "gl": criteria.country,
            "api_key": self._api_key,
        }

        try:
            data = self._request(params, endpoint="google_organic")
        except QuotaExceededError:
            return []

        organic_results = data.get("organic_results", [])
        collected: list[BusinessResult] = []

        for entry in organic_results[:remaining]:
            link = entry.get("link")
            collected.append(
                BusinessResult(
                    company_name=entry.get("title", "Unknown"),
                    website_url=link,
                    source="serpapi_organic",
                    raw_source_data=entry,
                )
            )

        return collected

    # -- Shared request/cache/quota plumbing ---------------------------------

    def _request(self, params: dict[str, Any], *, endpoint: str) -> dict[str, Any]:
        request_hash = compute_request_hash(params)
        cached = self._cache.get(request_hash)

        if cached is not None:
            self.usage_logs.append(
                UsageLogEntry(
                    provider="serpapi",
                    endpoint=endpoint,
                    request_hash=request_hash,
                    response_status=200,
                    credit_used=0,
                    duration_ms=0,
                    is_cached=True,
                    requested_at=datetime.now(timezone.utc),
                )
            )
            return cached

        self._quota.spend(1)

        start = time.monotonic()
        error_message: Optional[str] = None
        status_code: Optional[int] = None
        data: dict[str, Any] = {}

        try:
            response = self._client.get(self._base_url, params=params)
            status_code = response.status_code
            response.raise_for_status()
            data = response.json()
        except httpx.HTTPError as exc:
            error_message = _redact_api_key(str(exc))
            raise httpx.HTTPError(error_message) from exc
        finally:
            duration_ms = int((time.monotonic() - start) * 1000)
            self.usage_logs.append(
                UsageLogEntry(
                    provider="serpapi",
                    endpoint=endpoint,
                    request_hash=request_hash,
                    response_status=status_code,
                    credit_used=1,
                    duration_ms=duration_ms,
                    is_cached=False,
                    error_message=error_message,
                    requested_at=datetime.now(timezone.utc),
                )
            )

        if "error" in data:
            raise RuntimeError(f"SerpApi returned an error: {_redact_api_key(str(data['error']))}")

        self._cache.set(request_hash, data)

        return data
