"""Tracks research-job progress in Redis.

The Python service intentionally has no database of its own (Requirement.md
keeps SQLAlchemy optional) — Laravel is the system of record for
everything durable. This store exists only so GET /internal/v1/jobs/{id}
can answer a status poll without re-running the pipeline, and it's fine
for that state to be ephemeral (TTL-bound).
"""
from __future__ import annotations

import json
from typing import Any

import redis

_JOB_KEY_PREFIX = "research_job:"
_JOB_TTL_SECONDS = 60 * 60 * 24  # 1 day is plenty for a poll fallback


class JobStore:
    def __init__(self, redis_client: redis.Redis) -> None:
        self._redis = redis_client

    def _key(self, job_id: str) -> str:
        return f"{_JOB_KEY_PREFIX}{job_id}"

    def get(self, job_id: str) -> dict[str, Any] | None:
        raw = self._redis.get(self._key(job_id))
        return json.loads(raw) if raw else None

    def set(self, job_id: str, state: dict[str, Any]) -> None:
        self._redis.set(self._key(job_id), json.dumps(state), ex=_JOB_TTL_SECONDS)

    def update(self, job_id: str, **fields: Any) -> dict[str, Any]:
        state = self.get(job_id) or {}
        state.update(fields)
        self.set(job_id, state)
        return state
