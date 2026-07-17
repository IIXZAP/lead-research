import json
import time

import pytest
from fastapi.testclient import TestClient

import app.api.internal as internal_api
from app.core.config import settings
from app.main import app
from app.security.hmac import sign
from app.services.job_store import JobStore


@pytest.fixture(autouse=True)
def _patch_redis(monkeypatch, fake_redis):
    monkeypatch.setattr(internal_api, "get_redis_client", lambda *a, **k: fake_redis)
    return fake_redis


@pytest.fixture
def client():
    return TestClient(app)


def _signed_headers(body: str, idempotency_key: str = "idem-1") -> dict:
    timestamp = str(int(time.time()))
    signature = sign(settings.internal_shared_secret, timestamp, body)

    return {
        "X-Internal-Key": "laravel",
        "X-Timestamp": timestamp,
        "X-Signature": signature,
        "X-Idempotency-Key": idempotency_key,
        "Content-Type": "application/json",
    }


def _process_request_body(campaign_id: int = 1) -> str:
    return json.dumps(
        {
            "campaign_id": campaign_id,
            "research_job_id": "job-abc",
            "criteria": {"business_keyword": "test"},
            "callback_url": "https://laravel.test/callback",
        }
    )


def test_process_rejects_missing_auth_headers(client):
    response = client.post("/internal/v1/campaigns/1/process", content=_process_request_body())

    assert response.status_code == 401


def test_process_rejects_invalid_signature(client):
    body = _process_request_body()
    headers = _signed_headers(body)
    headers["X-Signature"] = "wrong"

    response = client.post("/internal/v1/campaigns/1/process", content=body, headers=headers)

    assert response.status_code == 401


def test_process_accepts_valid_signed_request(client, monkeypatch):
    called = {}
    monkeypatch.setattr(
        internal_api.process_campaign,
        "delay",
        lambda **kwargs: called.update(kwargs),
    )

    body = _process_request_body()
    response = client.post("/internal/v1/campaigns/1/process", content=body, headers=_signed_headers(body))

    assert response.status_code == 202
    assert response.json()["job_id"] == "job-abc"
    assert called["campaign_id"] == 1


def test_process_rejects_campaign_id_mismatch(client):
    body = _process_request_body(campaign_id=999)
    response = client.post("/internal/v1/campaigns/1/process", content=body, headers=_signed_headers(body))

    assert response.status_code == 400


def test_process_rejects_replayed_idempotency_key(client, monkeypatch):
    monkeypatch.setattr(internal_api.process_campaign, "delay", lambda **kwargs: None)

    body = _process_request_body()
    headers = _signed_headers(body, idempotency_key="same-key")

    first = client.post("/internal/v1/campaigns/1/process", content=body, headers=headers)
    second = client.post("/internal/v1/campaigns/1/process", content=body, headers=headers)

    assert first.status_code == 202
    assert second.status_code == 409


def test_get_job_returns_404_for_unknown_job(client):
    response = client.get("/internal/v1/jobs/does-not-exist")

    assert response.status_code == 404


def test_get_job_returns_current_state(client, _patch_redis):
    job_store = JobStore(_patch_redis)
    job_store.set(
        "job-xyz",
        {
            "status": "processing",
            "current_stage": "website_audit",
            "progress_percent": 45,
            "total_items": 100,
            "processed_items": 45,
            "successful_items": 42,
            "failed_items": 3,
        },
    )

    response = client.get("/internal/v1/jobs/job-xyz")

    assert response.status_code == 200
    assert response.json()["progress_percent"] == 45
