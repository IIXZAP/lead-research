"""Sends signed, idempotent callbacks to Laravel (Requirement.md §11/§12).

Each call gets its own idempotency key so a retried callback (e.g. after
a transient network error) is safe to process twice on Laravel's side —
Laravel is expected to treat a repeated key as a no-op, not a duplicate
insert.
"""
from __future__ import annotations

import time
import uuid

import httpx
from tenacity import retry, retry_if_exception_type, stop_after_attempt, wait_exponential

from app.core.logging import get_logger
from app.schemas.callback import CallbackPayload
from app.security.hmac import sign

logger = get_logger(__name__)


class CallbackError(Exception):
    """Raised after retries are exhausted — the caller decides whether
    this is recoverable (task retry) or should be logged as permanent."""


class CallbackClient:
    def __init__(
        self,
        *,
        shared_secret: str,
        timeout_seconds: int = 15,
        max_attempts: int = 3,
        client: httpx.Client | None = None,
    ) -> None:
        self._shared_secret = shared_secret
        self._timeout_seconds = timeout_seconds
        self._max_attempts = max_attempts
        self._owns_client = client is None
        self._client = client or httpx.Client(timeout=httpx.Timeout(timeout_seconds))

    def close(self) -> None:
        if self._owns_client:
            self._client.close()

    def send(self, callback_url: str, payload: CallbackPayload) -> httpx.Response:
        body = payload.model_dump_json()
        idempotency_key = str(uuid.uuid4())

        return self._send_with_retry(callback_url, body, idempotency_key)

    def _send_with_retry(self, callback_url: str, body: str, idempotency_key: str) -> httpx.Response:
        @retry(
            reraise=True,
            stop=stop_after_attempt(self._max_attempts),
            wait=wait_exponential(multiplier=1, min=1, max=10),
            retry=retry_if_exception_type((httpx.TransportError, httpx.HTTPStatusError)),
        )
        def _attempt() -> httpx.Response:
            timestamp = str(int(time.time()))
            signature = sign(self._shared_secret, timestamp, body)

            response = self._client.post(
                callback_url,
                content=body,
                headers={
                    "Content-Type": "application/json",
                    "X-Internal-Key": "research-agent",
                    "X-Timestamp": timestamp,
                    "X-Signature": signature,
                    "X-Idempotency-Key": idempotency_key,
                },
            )
            response.raise_for_status()
            return response

        try:
            return _attempt()
        except (httpx.TransportError, httpx.HTTPStatusError) as exc:
            logger.error(
                "callback delivery failed after retries",
                extra={"context": {"callback_url": callback_url, "error": str(exc)}},
            )
            raise CallbackError(str(exc)) from exc
