"""Single place that constructs Redis connections, so tests can swap in
a fake client without patching every call site individually."""

from __future__ import annotations

from functools import lru_cache

import redis

from app.core.config import settings


@lru_cache
def get_redis_client(db: int | None = None) -> redis.Redis:
    return redis.Redis(
        host=settings.redis_host,
        port=settings.redis_port,
        username="default" if settings.redis_password else None,
        password=settings.redis_password or None,
        db=db if db is not None else settings.celery_result_db,
    )
