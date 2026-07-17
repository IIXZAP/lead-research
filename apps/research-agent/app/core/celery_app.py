"""Celery application instance shared by all tasks in app/tasks/*.

`include=` is required here, not optional: the FastAPI process (python-api
container) already imports app.tasks.campaign_tasks itself (to call
.delay()), so its own Celery app instance has tasks registered as a side
effect — but the celery-worker container runs this same module in a
*separate* process via `celery -A app.core.celery_app worker`, and never
imports the task modules any other way. Without `include=` here, that
worker's task registry is empty and every dispatched task is rejected
with "Received unregistered task" even though the broker message itself
is delivered correctly.
"""
from __future__ import annotations

from celery import Celery

from app.core.config import settings

celery_app = Celery(
    "research_agent",
    broker=settings.celery_broker_url,
    backend=settings.celery_result_backend,
    include=[
        "app.tasks.search_tasks",
        "app.tasks.audit_tasks",
        "app.tasks.campaign_tasks",
    ],
)

celery_app.conf.update(
    task_serializer="json",
    result_serializer="json",
    accept_content=["json"],
    task_track_started=True,
    task_acks_late=True,
    worker_prefetch_multiplier=1,
    task_time_limit=600,
    task_soft_time_limit=540,
)
