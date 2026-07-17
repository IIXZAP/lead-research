"""Payload Python sends back to Laravel per Requirement.md §11."""
from __future__ import annotations

from typing import Any, Literal, Optional

from pydantic import BaseModel, Field

from app.schemas.audit import Issue
from app.schemas.usage import UsageLogEntry


class CallbackProgress(BaseModel):
    total_items: int
    processed_items: int
    successful_items: int
    failed_items: int


class CallbackLead(BaseModel):
    company_name: str
    phone: Optional[str] = None
    website_url: Optional[str] = None
    address: Optional[str] = None
    province: Optional[str] = None
    business_type: Optional[str] = None
    source: str
    source_place_id: Optional[str] = None
    website_issue: str
    issue_codes: list[str] = Field(default_factory=list)
    audit_score: Optional[int] = None
    confidence_score: Optional[float] = None
    issues: list[Issue] = Field(default_factory=list)
    evidence: dict[str, Any] = Field(default_factory=dict)
    raw_source_data: dict[str, Any] = Field(default_factory=dict)


class CallbackError(BaseModel):
    stage: str
    message: str
    context: dict[str, Any] = Field(default_factory=dict)


class CallbackPayload(BaseModel):
    job_id: str
    campaign_id: int
    status: Literal["processing", "partially_completed", "completed", "failed"]
    progress: CallbackProgress
    leads: list[CallbackLead] = Field(default_factory=list)
    errors: list[CallbackError] = Field(default_factory=list)
    usage_logs: list[UsageLogEntry] = Field(default_factory=list)
