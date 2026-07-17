"""Tracks every outbound SerpApi call so Laravel's api_usage_logs table
(Requirement.md §5) has a full record — including cached hits, which
cost no credit but are still worth knowing about.
"""
from __future__ import annotations

from datetime import datetime
from typing import Optional

from pydantic import BaseModel


class UsageLogEntry(BaseModel):
    provider: str
    endpoint: str
    request_hash: str
    response_status: Optional[int] = None
    credit_used: int = 0
    duration_ms: Optional[int] = None
    is_cached: bool = False
    error_message: Optional[str] = None
    requested_at: datetime
