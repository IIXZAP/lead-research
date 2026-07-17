"""Schemas for the Laravel <-> Python internal API contract (Requirement.md §11)."""
from __future__ import annotations

from typing import Literal, Optional

from pydantic import BaseModel

from app.schemas.business import SearchCriteria


class CampaignProcessRequest(BaseModel):
    campaign_id: int
    research_job_id: str
    criteria: SearchCriteria
    callback_url: str


class CampaignProcessResponse(BaseModel):
    job_id: str
    status: Literal["queued"] = "queued"


class JobStatusResponse(BaseModel):
    job_id: str
    status: str
    current_stage: Optional[str] = None
    progress_percent: int = 0
    total_items: int = 0
    processed_items: int = 0
    successful_items: int = 0
    failed_items: int = 0
