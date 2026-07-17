"""Audit-stage Celery tasks (Requirement.md §12)."""
from __future__ import annotations

from datetime import datetime, timezone

from app.core.celery_app import celery_app
from app.core.config import settings
from app.schemas.audit import AuditMetrics, AuditResult
from app.services.issue_classifier import build_summary, classify
from app.services.score_calculator import compute_confidence, compute_score
from app.services.website_auditor import WebsiteAuditor


@celery_app.task(name="audit_website", bind=True, max_retries=2, soft_time_limit=60)
def audit_website(self, website_url: str | None) -> dict:
    if not website_url:
        metrics = AuditMetrics(website_url=None)
    else:
        with WebsiteAuditor(
            timeout_seconds=settings.website_audit_timeout_seconds,
            max_links=settings.website_audit_max_links,
            slow_threshold_ms=settings.website_audit_slow_threshold_ms,
            user_agent=settings.website_audit_user_agent,
        ) as auditor:
            metrics = auditor.audit(website_url)

    return metrics.model_dump()


@celery_app.task(name="build_issue_summary", bind=True, max_retries=1)
def build_issue_summary(self, lead_reference: str, metrics_payload: dict) -> dict:
    metrics = AuditMetrics(**metrics_payload)
    issues = classify(metrics, slow_threshold_ms=settings.website_audit_slow_threshold_ms)
    issue_codes = [issue.code for issue in issues]

    result = AuditResult(
        lead_reference=lead_reference,
        metrics=metrics,
        issues=issues,
        issue_codes=issue_codes,
        audit_score=compute_score(issue_codes),
        confidence_score=compute_confidence(issues),
        audited_at=datetime.now(timezone.utc),
    )

    return {
        **result.model_dump(mode="json"),
        "summary_th": build_summary(issues),
    }
