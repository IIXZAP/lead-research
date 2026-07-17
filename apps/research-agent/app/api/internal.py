"""Internal API router — the Laravel <-> Python contract from Requirement.md §11.

Phase 2 only had health/ready. Phase 4 adds the two real endpoints:
process a campaign (kicks off the Celery pipeline) and poll job status.
"""
from __future__ import annotations

import json

from fastapi import APIRouter, Depends, HTTPException, Request, status

from app.core.config import settings
from app.core.redis_client import get_redis_client
from app.schemas.campaign import CampaignProcessRequest, CampaignProcessResponse, JobStatusResponse
from app.security.internal_auth import IdempotencyStore, verify_internal_request
from app.services.job_store import JobStore
from app.tasks.campaign_tasks import process_campaign

router = APIRouter(prefix="/internal/v1", tags=["internal"])


def get_idempotency_store() -> IdempotencyStore:
    return IdempotencyStore(get_redis_client(), ttl_seconds=settings.internal_signature_ttl_seconds)


def get_job_store() -> JobStore:
    return JobStore(get_redis_client())


@router.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok", "service": settings.service_name}


@router.get("/ready")
def ready() -> dict[str, str]:
    try:
        get_redis_client().ping()
    except Exception:  # noqa: BLE001
        raise HTTPException(status.HTTP_503_SERVICE_UNAVAILABLE, "Redis unavailable")

    return {"status": "ready"}


@router.post(
    "/campaigns/{campaign_id}/process",
    status_code=status.HTTP_202_ACCEPTED,
    response_model=CampaignProcessResponse,
)
async def process(
    campaign_id: int,
    request: Request,
    idempotency_store: IdempotencyStore = Depends(get_idempotency_store),
) -> CampaignProcessResponse:
    body = await verify_internal_request(request, settings, idempotency_store)
    payload = CampaignProcessRequest(**json.loads(body))

    if payload.campaign_id != campaign_id:
        raise HTTPException(status.HTTP_400_BAD_REQUEST, "campaign_id mismatch between path and body")

    process_campaign.delay(
        job_id=payload.research_job_id,
        campaign_id=payload.campaign_id,
        criteria=payload.criteria.model_dump(),
        callback_url=payload.callback_url,
    )

    return CampaignProcessResponse(job_id=payload.research_job_id)


@router.get("/jobs/{job_id}", response_model=JobStatusResponse)
def get_job(job_id: str, job_store: JobStore = Depends(get_job_store)) -> JobStatusResponse:
    state = job_store.get(job_id)

    if state is None:
        raise HTTPException(status.HTTP_404_NOT_FOUND, "Unknown job_id")

    return JobStatusResponse(job_id=job_id, **state)
