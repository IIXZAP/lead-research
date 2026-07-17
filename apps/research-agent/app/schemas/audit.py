"""Schemas for website audit metrics, issues, and evidence (Requirement.md §8/§9)."""
from __future__ import annotations

from datetime import datetime
from typing import Any, Literal, Optional

from pydantic import BaseModel, Field

Severity = Literal["critical", "high", "medium", "low", "opportunity"]
Confidence = Literal["high", "medium", "low"]


class Issue(BaseModel):
    code: str
    severity: Severity
    confidence: Confidence
    message_th: str
    evidence: dict[str, Any] = Field(default_factory=dict)


class AuditMetrics(BaseModel):
    """Raw, deterministic measurements the auditor collected. This is the
    only thing issue_classifier.py is allowed to reason from — nothing
    here is inferred or guessed."""

    website_url: Optional[str] = None
    final_url: Optional[str] = None
    http_status: Optional[int] = None
    https_enabled: Optional[bool] = None
    ssl_valid: Optional[bool] = None
    response_time_ms: Optional[int] = None
    page_size_bytes: Optional[int] = None
    mobile_viewport_found: Optional[bool] = None
    title_found: Optional[bool] = None
    meta_description_found: Optional[bool] = None
    contact_information_found: Optional[bool] = None
    contact_form_found: Optional[bool] = None
    broken_link_count: int = 0
    redirect_count: int = 0
    mixed_content_found: bool = False
    dns_resolved: bool = True
    connection_error: Optional[str] = None
    checked_link_count: int = 0


class AuditResult(BaseModel):
    lead_reference: str
    metrics: AuditMetrics
    issues: list[Issue] = Field(default_factory=list)
    issue_codes: list[str] = Field(default_factory=list)
    audit_score: int
    confidence_score: float
    audited_at: datetime
