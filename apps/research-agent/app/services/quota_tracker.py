"""Enforces MAX_SERPAPI_REQUESTS_PER_CAMPAIGN (Requirement.md §14) so one
runaway campaign can't burn through the whole SerpApi budget. Cached
responses never count against the quota — only real, billed requests do.
"""
from __future__ import annotations

import redis

_QUOTA_KEY_PREFIX = "serpapi_quota:"
_QUOTA_TTL_SECONDS = 60 * 60 * 24  # a campaign's run shouldn't span more than a day


class QuotaExceededError(Exception):
    pass


class QuotaTracker:
    def __init__(self, redis_client: redis.Redis, campaign_id: int, max_requests: int) -> None:
        self._redis = redis_client
        self._key = f"{_QUOTA_KEY_PREFIX}{campaign_id}"
        self._max_requests = max_requests

    def remaining(self) -> int:
        used = int(self._redis.get(self._key) or 0)
        return max(0, self._max_requests - used)

    def spend(self, count: int = 1) -> None:
        """Raises if this would exceed quota; only increments if allowed."""
        if self.remaining() < count:
            raise QuotaExceededError(
                f"SerpApi quota exhausted for this campaign (max {self._max_requests} requests)"
            )

        pipe = self._redis.pipeline()
        pipe.incrby(self._key, count)
        pipe.expire(self._key, _QUOTA_TTL_SECONDS)
        pipe.execute()
