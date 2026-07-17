from __future__ import annotations

from fastapi import FastAPI

from app.api.internal import router as internal_router
from app.core.config import settings
from app.core.logging import configure_logging

configure_logging()

app = FastAPI(title="Lead Research Agent", version="0.1.0")
app.include_router(internal_router)


@app.get("/")
def root() -> dict[str, str]:
    return {"service": settings.service_name, "environment": settings.environment}
