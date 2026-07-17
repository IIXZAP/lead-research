"""Verifies that an incoming request really came from Laravel
(Requirement.md §11): signature, timestamp freshness, and idempotency.
"""
from __future__ import annotations

from fastapi import HTTPException, Request, status

from app.core.config import Settings
from app.security.hmac import verify


class IdempotencyStore:
    """Minimal replay guard: the first time a key is seen it's accepted
    and remembered; every subsequent use within the TTL is rejected."""

    def __init__(self, redis_client, ttl_seconds: int = 300) -> None:
        self._redis = redis_client
        self._ttl_seconds = ttl_seconds

    def check_and_remember(self, key: str) -> bool:
        """Returns True if this is the first time `key` has been seen."""
        return bool(self._redis.set(f"idempotency:{key}", "1", nx=True, ex=self._ttl_seconds))


async def verify_internal_request(
    request: Request,
    settings: Settings,
    idempotency_store: IdempotencyStore,
) -> bytes:
    body = await request.body()

    internal_key = request.headers.get("x-internal-key")
    timestamp = request.headers.get("x-timestamp")
    signature = request.headers.get("x-signature")
    idempotency_key = request.headers.get("x-idempotency-key")

    if not all([internal_key, timestamp, signature, idempotency_key]):
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Missing internal auth headers")

    if not verify(
        settings.internal_shared_secret,
        timestamp,
        body,
        signature,
        settings.internal_signature_ttl_seconds,
    ):
        raise HTTPException(status.HTTP_401_UNAUTHORIZED, "Invalid or expired signature")

    if not idempotency_store.check_and_remember(idempotency_key):
        raise HTTPException(status.HTTP_409_CONFLICT, "Duplicate request (idempotency key reused)")

    return body
