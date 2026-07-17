from __future__ import annotations

import fakeredis
import pytest

from app.core.celery_app import celery_app
from app.core.config import settings


@pytest.fixture(autouse=True)
def _test_settings():
    original_secret = settings.internal_shared_secret
    original_ttl = settings.internal_signature_ttl_seconds
    settings.internal_shared_secret = "test-shared-secret"
    settings.internal_signature_ttl_seconds = 300
    yield settings
    settings.internal_shared_secret = original_secret
    settings.internal_signature_ttl_seconds = original_ttl


@pytest.fixture(autouse=True)
def _eager_celery():
    celery_app.conf.task_always_eager = True
    celery_app.conf.task_eager_propagates = True
    yield
    celery_app.conf.task_always_eager = False


@pytest.fixture
def fake_redis():
    return fakeredis.FakeRedis()
