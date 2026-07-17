"""Response cache for SerpApi calls (Requirement.md §6/§14: SERPAPI_CACHE_TTL_HOURS).

Keyed by a hash of the exact request parameters, not just the query
string — two requests with the same query but different pagination
offsets are different cache entries.
"""
from __future__ import annotations

import hashlib
import json
from typing import Any

import redis

_CACHE_KEY_PREFIX = "serpapi_cache:"


def compute_request_hash(params: dict[str, Any]) -> str:
    # Sort keys so identical params always hash the same regardless of
    # dict insertion order.
    canonical = json.dumps(params, sort_keys=True, default=str)
    return hashlib.sha256(canonical.encode("utf-8")).hexdigest()


class SerpApiCache:
    def __init__(self, redis_client: redis.Redis, ttl_hours: int = 24) -> None:
        self._redis = redis_client
        self._ttl_seconds = ttl_hours * 3600

    def get(self, request_hash: str) -> dict[str, Any] | None:
        raw = self._redis.get(f"{_CACHE_KEY_PREFIX}{request_hash}")
        return json.loads(raw) if raw else None

    def set(self, request_hash: str, response: dict[str, Any]) -> None:
        self._redis.set(
            f"{_CACHE_KEY_PREFIX}{request_hash}",
            json.dumps(response),
            ex=self._ttl_seconds,
        )
