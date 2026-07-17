"""Search-stage Celery tasks (Requirement.md §12).

Each is a real, independently-dispatchable Celery task. `process_campaign`
(campaign_tasks.py) calls them synchronously via `.run(...)` for the mock
pipeline's simplicity today; nothing stops a future phase from chaining
them with `.delay()`/`chain()` once distributing work across workers
actually matters at scale.
"""
from __future__ import annotations

from app.core.celery_app import celery_app
from app.core.config import settings
from app.providers.factory import get_provider
from app.schemas.business import SearchCriteria
from app.services.query_builder import build_queries


@celery_app.task(name="generate_search_queries", bind=True, max_retries=2)
def generate_search_queries(self, criteria: dict) -> list[str]:
    return build_queries(SearchCriteria(**criteria))


@celery_app.task(name="search_serpapi_page", bind=True, max_retries=3)
def search_serpapi_page(self, criteria: dict, campaign_id: int) -> dict:
    """Runs the full search for a campaign (all query variants and pages
    for SerpApi; the fixed dataset for mock) in one call, since the
    provider itself owns query/pagination strategy. Returns both the
    businesses found and every usage log entry generated along the way,
    since the provider instance — and its usage_logs — would otherwise
    be discarded when this task returns.
    """
    provider = get_provider(settings, campaign_id=campaign_id)
    try:
        results = provider.search_businesses(SearchCriteria(**criteria))
        usage_logs = [log.model_dump(mode="json") for log in getattr(provider, "usage_logs", [])]
    finally:
        close = getattr(provider, "close", None)
        if callable(close):
            close()

    return {
        "businesses": [result.model_dump() for result in results],
        "usage_logs": usage_logs,
    }


@celery_app.task(name="fetch_place_detail", bind=True, max_retries=3)
def fetch_place_detail(self, place_reference: str, campaign_id: int) -> dict:
    provider = get_provider(settings, campaign_id=campaign_id)
    try:
        detail = provider.get_business_detail(place_reference)
    finally:
        close = getattr(provider, "close", None)
        if callable(close):
            close()

    return detail.model_dump()
