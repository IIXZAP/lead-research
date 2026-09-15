"""Orchestrates the full campaign pipeline (Requirement.md §12):

    Campaign -> generate queries -> search -> (fetch detail if needed)
             -> normalize -> deduplicate -> audit website -> classify issues
             -> send results to Laravel

Runs as a single Celery task rather than a long chain of `.delay()` calls
so one job's state lives in one place and partial failure per-lead is
easy to reason about — but every stage still goes through the same
service functions and task-wrapped units used elsewhere (audit_website,
build_issue_summary), so nothing here duplicates logic.
"""
from __future__ import annotations

from app.core.celery_app import celery_app
from app.core.config import settings
from app.core.logging import get_logger
from app.core.redis_client import get_redis_client
from app.schemas.business import BusinessResult, SearchCriteria
from app.schemas.callback import (
    CallbackError,
    CallbackLead,
    CallbackPayload,
    CallbackProgress,
)
from app.services.callback_client import CallbackClient
from app.services.callback_client import CallbackError as CallbackDeliveryError
from app.services.deduplicator import deduplicate
from app.services.job_store import JobStore
from app.tasks.audit_tasks import audit_website, build_issue_summary
from app.tasks.search_tasks import generate_search_queries, search_serpapi_page

logger = get_logger(__name__)

CALLBACK_CHUNK_SIZE = 5


# def _redis_client():
#     import redis

#     return redis.Redis(host=settings.redis_host, port=settings.redis_port, db=settings.celery_result_db)


@celery_app.task(name="process_campaign", bind=True, max_retries=1, soft_time_limit=1800)
def process_campaign(
    self,
    job_id: str,
    campaign_id: int,
    criteria: dict,
    callback_url: str,
) -> dict:
    job_store = JobStore(get_redis_client())
    callback_client = CallbackClient(shared_secret=settings.internal_shared_secret)

    job_store.set(
        job_id,
        {
            "status": "processing",
            "current_stage": "generate_queries",
            "progress_percent": 0,
            "total_items": 0,
            "processed_items": 0,
            "successful_items": 0,
            "failed_items": 0,
        },
    )

    errors: list[CallbackError] = []

    try:
        queries = generate_search_queries.run(criteria)
        job_store.update(job_id, current_stage="search")

        search_criteria = SearchCriteria(**criteria)
        search_result = search_serpapi_page.run(criteria, campaign_id)
        raw_businesses: list[BusinessResult] = [
            BusinessResult(**item) for item in search_result["businesses"]
        ]
        usage_logs: list[dict] = search_result["usage_logs"]

        raw_businesses = raw_businesses[: search_criteria.maximum_leads]

        job_store.update(job_id, current_stage="deduplicate", total_items=len(raw_businesses))

        dedup_result = deduplicate(raw_businesses)

        total_items = len(dedup_result.unique)
        job_store.update(job_id, current_stage="website_audit", total_items=total_items)

        leads: list[CallbackLead] = []
        successful_items = 0
        failed_items = 0

        for index, entry in enumerate(dedup_result.unique, start=1):
            business = entry.business

            try:
                metrics_payload = audit_website.run(business.website_url)
                audit = build_issue_summary.run(
                    lead_reference=business.source_place_id or business.company_name,
                    metrics_payload=metrics_payload,
                )

                lead = CallbackLead(
                    company_name=business.company_name,
                    phone=business.phone,
                    website_url=business.website_url,
                    address=business.address,
                    province=business.province,
                    business_type=business.business_type,
                    source=business.source,
                    source_place_id=business.source_place_id,
                    website_issue=audit["summary_th"],
                    issue_codes=audit["issue_codes"],
                    audit_score=audit["audit_score"],
                    confidence_score=audit["confidence_score"],
                    issues=audit["issues"],
                    evidence={"potential_duplicate": entry.potential_duplicate},
                    raw_source_data=business.raw_source_data,
                )
                leads.append(lead)
                successful_items += 1
            except Exception as exc:  # noqa: BLE001 - a single lead failing must not sink the campaign
                failed_items += 1
                errors.append(
                    CallbackError(
                        stage="website_audit",
                        message=str(exc),
                        context={"company_name": business.company_name},
                    )
                )
                logger.error(
                    "lead processing failed",
                    extra={"context": {"job_id": job_id, "company_name": business.company_name}},
                )

            job_store.update(
                job_id,
                processed_items=index,
                successful_items=successful_items,
                failed_items=failed_items,
                progress_percent=int((index / total_items) * 100) if total_items else 100,
            )

            if len(leads) >= CALLBACK_CHUNK_SIZE or index == total_items:
                _send_progress(
                    callback_client,
                    callback_url,
                    job_id=job_id,
                    campaign_id=campaign_id,
                    status="processing",
                    total_items=total_items,
                    processed_items=index,
                    successful_items=successful_items,
                    failed_items=failed_items,
                    leads=leads,
                    errors=[],
                    usage_logs=[],
                )
                leads = []

        final_status = _final_status(total_items, successful_items, failed_items)

        job_store.update(job_id, status=final_status, current_stage="completed", progress_percent=100)

        _send_progress(
            callback_client,
            callback_url,
            job_id=job_id,
            campaign_id=campaign_id,
            status=final_status,
            total_items=total_items,
            processed_items=total_items,
            successful_items=successful_items,
            failed_items=failed_items,
            leads=[],
            errors=errors,
            usage_logs=usage_logs,
        )

        return {"job_id": job_id, "status": final_status}

    except Exception as exc:
        job_store.update(job_id, status="failed", current_stage="failed")
        logger.error("campaign processing failed", extra={"context": {"job_id": job_id, "error": str(exc)}})

        try:
            _send_progress(
                callback_client,
                callback_url,
                job_id=job_id,
                campaign_id=campaign_id,
                status="failed",
                total_items=0,
                processed_items=0,
                successful_items=0,
                failed_items=0,
                leads=[],
                errors=[CallbackError(stage="process_campaign", message=str(exc))],
                usage_logs=locals().get("usage_logs", []),
            )
        except CallbackDeliveryError:
            logger.error("failed to notify Laravel of campaign failure", extra={"context": {"job_id": job_id}})

        raise
    finally:
        callback_client.close()


def _final_status(total_items: int, successful_items: int, failed_items: int) -> str:
    if total_items == 0:
        return "completed"
    if failed_items == 0:
        return "completed"
    if successful_items == 0:
        return "failed"
    return "partially_completed"


def _send_progress(
    callback_client: CallbackClient,
    callback_url: str,
    *,
    job_id: str,
    campaign_id: int,
    status: str,
    total_items: int,
    processed_items: int,
    successful_items: int,
    failed_items: int,
    leads: list[CallbackLead],
    errors: list[CallbackError],
    usage_logs: list[dict],
) -> None:
    payload = CallbackPayload(
        job_id=job_id,
        campaign_id=campaign_id,
        status=status,
        progress=CallbackProgress(
            total_items=total_items,
            processed_items=processed_items,
            successful_items=successful_items,
            failed_items=failed_items,
        ),
        leads=leads,
        errors=errors,
        usage_logs=usage_logs,
    )

    callback_client.send(callback_url, payload)
