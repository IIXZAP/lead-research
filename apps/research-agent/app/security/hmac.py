"""HMAC-SHA256 request signing shared with Laravel's internal API guard.

Both sides sign `f"{timestamp}.{raw_body}"` with the same shared secret
and compare in constant time. This module only handles the signature
itself — replay-window and idempotency-key bookkeeping live in the
caller (api/internal.py for verifying, callback_client.py for signing).
"""
from __future__ import annotations

import hashlib
import hmac
import time


def sign(secret: str, timestamp: str, body: bytes | str) -> str:
    if isinstance(body, str):
        body = body.encode("utf-8")

    message = timestamp.encode("utf-8") + b"." + body

    return hmac.new(secret.encode("utf-8"), message, hashlib.sha256).hexdigest()


def verify(
    secret: str,
    timestamp: str,
    body: bytes | str,
    signature: str,
    ttl_seconds: int,
    now: int | None = None,
) -> bool:
    now = now if now is not None else int(time.time())

    try:
        request_time = int(timestamp)
    except (TypeError, ValueError):
        return False

    if abs(now - request_time) > ttl_seconds:
        return False

    expected = sign(secret, timestamp, body)

    return hmac.compare_digest(expected, signature)
