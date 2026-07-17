"""Picks a SearchProvider based on RESEARCH_PROVIDER (Requirement.md §17)."""
from __future__ import annotations

from app.core.config import Settings
from app.core.redis_client import get_redis_client
from app.providers.base import SearchProvider
from app.providers.mock_provider import MockProvider
from app.providers.serpapi_provider import SerpApiGoogleMapsProvider
from app.services.quota_tracker import QuotaTracker
from app.services.serpapi_cache import SerpApiCache


def get_provider(settings: Settings, campaign_id: int | None = None) -> SearchProvider:
    if settings.research_provider == "mock":
        return MockProvider()

    if settings.research_provider == "serpapi":
        if not settings.serpapi_api_key:
            raise ValueError("SERPAPI_API_KEY is not set but RESEARCH_PROVIDER=serpapi")

        cache = SerpApiCache(get_redis_client(), ttl_hours=settings.serpapi_cache_ttl_hours)
        quota = QuotaTracker(
            get_redis_client(),
            campaign_id=campaign_id or 0,
            max_requests=settings.max_serpapi_requests_per_campaign,
        )

        return SerpApiGoogleMapsProvider(
            api_key=settings.serpapi_api_key,
            base_url=settings.serpapi_base_url,
            cache=cache,
            quota=quota,
            timeout_seconds=settings.serpapi_timeout_seconds,
            max_retries=settings.serpapi_max_retries,
        )

    raise ValueError(f"Unknown RESEARCH_PROVIDER: {settings.research_provider!r}")
