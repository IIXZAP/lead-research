import httpx
import pytest
import respx

from app.schemas.callback import CallbackPayload, CallbackProgress
from app.security.hmac import verify
from app.services.callback_client import CallbackClient, CallbackError


def _payload() -> CallbackPayload:
    return CallbackPayload(
        job_id="job-1",
        campaign_id=1,
        status="completed",
        progress=CallbackProgress(total_items=1, processed_items=1, successful_items=1, failed_items=0),
    )


def test_send_signs_request_with_valid_hmac_headers():
    captured = {}

    def handler(request: httpx.Request) -> httpx.Response:
        captured["headers"] = request.headers
        captured["body"] = request.content
        return httpx.Response(200, json={"ok": True})

    with respx.mock(assert_all_called=False) as router:
        router.post("https://laravel.test/callback").mock(side_effect=handler)

        client = CallbackClient(shared_secret="test-secret")
        client.send("https://laravel.test/callback", _payload())
        client.close()

    headers = captured["headers"]
    assert "x-signature" in headers
    assert "x-idempotency-key" in headers
    assert verify(
        "test-secret",
        headers["x-timestamp"],
        captured["body"],
        headers["x-signature"],
        ttl_seconds=300,
    )


def test_send_retries_then_raises_callback_error_on_repeated_failure():
    with respx.mock(assert_all_called=False) as router:
        router.post("https://laravel.test/callback").mock(
            return_value=httpx.Response(500, json={"error": "boom"})
        )

        client = CallbackClient(shared_secret="test-secret", max_attempts=2)

        with pytest.raises(CallbackError):
            client.send("https://laravel.test/callback", _payload())

        client.close()

        assert router.calls.call_count == 2


def test_each_send_uses_a_different_idempotency_key():
    keys = []

    def handler(request: httpx.Request) -> httpx.Response:
        keys.append(request.headers["x-idempotency-key"])
        return httpx.Response(200, json={"ok": True})

    with respx.mock(assert_all_called=False) as router:
        router.post("https://laravel.test/callback").mock(side_effect=handler)

        client = CallbackClient(shared_secret="test-secret")
        client.send("https://laravel.test/callback", _payload())
        client.send("https://laravel.test/callback", _payload())
        client.close()

    assert keys[0] != keys[1]
