"""Audit score: starts at 100, subtracts a configured weight per issue
code found, clamped to [0, 100] (Requirement.md §10). Weights live in
config, never hardcoded inline, so they can be tuned without a code
change.
"""
from __future__ import annotations

DEFAULT_ISSUE_WEIGHTS: dict[str, int] = {
    "WEBSITE_NOT_FOUND": 100,
    "DNS_ERROR": 50,
    "CONNECTION_TIMEOUT": 45,
    "HTTP_SERVER_ERROR": 40,
    "SSL_INVALID": 30,
    "HTTP_CLIENT_ERROR": 25,
    "HTTPS_NOT_ENABLED": 20,
    "EXCESSIVE_REDIRECTS": 15,
    "SLOW_RESPONSE": 15,
    "MOBILE_VIEWPORT_MISSING": 15,
    "MIXED_CONTENT": 15,
    "CONTACT_INFORMATION_MISSING": 10,
    "BROKEN_INTERNAL_LINKS": 10,
    "TITLE_MISSING": 5,
    "META_DESCRIPTION_MISSING": 5,
    "CONTACT_FORM_MISSING": 5,
    "OUTDATED_SIGNAL": 10,
}


def compute_score(issue_codes: list[str], weights: dict[str, int] | None = None) -> int:
    weights = weights or DEFAULT_ISSUE_WEIGHTS
    score = 100 - sum(weights.get(code, 0) for code in issue_codes)

    return max(0, min(100, score))


def compute_confidence(issues: list) -> float:
    """Average confidence across detected issues, mapped to a 0-1 scale.
    A website with no issues detected is reported at full confidence in
    the (lack of) findings."""
    if not issues:
        return 1.0

    weight = {"high": 1.0, "medium": 0.6, "low": 0.3}
    scores = [weight.get(issue.confidence, 0.5) for issue in issues]

    return round(sum(scores) / len(scores), 3)
