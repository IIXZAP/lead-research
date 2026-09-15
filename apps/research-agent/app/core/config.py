"""Central settings for the research-agent service.

Every module imports `settings` from here instead of reading os.environ
directly, so there is exactly one place that knows about env var names.
"""

from __future__ import annotations

from functools import lru_cache

from pydantic import Field
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    # Service
    service_name: str = "research-agent"
    environment: str = Field(default="local", alias="APP_ENV")

    # Redis / Celery
    redis_host: str = Field(default="redis", alias="REDIS_HOST")
    redis_port: int = Field(default=6379, alias="REDIS_PORT")
    redis_password: str = Field(default="", alias="REDIS_PASSWORD")
    celery_broker_db: int = Field(default=2, alias="CELERY_BROKER_DB")
    celery_result_db: int = Field(default=3, alias="CELERY_RESULT_DB")

    @property
    def redis_auth(self) -> str:
        """Userinfo segment for a Redis URL — empty locally, ':password@' when set."""
        return f":{self.redis_password}@" if self.redis_password else ""

    # Internal service-to-service auth (HMAC)
    internal_shared_secret: str = Field(default="", alias="INTERNAL_SHARED_SECRET")
    internal_signature_ttl_seconds: int = Field(
        default=300, alias="INTERNAL_SIGNATURE_TTL_SECONDS"
    )

    # SerpApi
    research_provider: str = Field(default="mock", alias="RESEARCH_PROVIDER")
    serpapi_api_key: str = Field(default="", alias="SERPAPI_API_KEY")
    serpapi_base_url: str = Field(
        default="https://serpapi.com/search", alias="SERPAPI_BASE_URL"
    )
    serpapi_timeout_seconds: int = Field(default=30, alias="SERPAPI_TIMEOUT_SECONDS")
    serpapi_max_retries: int = Field(default=3, alias="SERPAPI_MAX_RETRIES")

    # Cost / quota control
    max_serpapi_requests_per_campaign: int = Field(
        default=50, alias="MAX_SERPAPI_REQUESTS_PER_CAMPAIGN"
    )
    max_leads_per_campaign: int = Field(default=500, alias="MAX_LEADS_PER_CAMPAIGN")
    serpapi_cache_ttl_hours: int = Field(default=24, alias="SERPAPI_CACHE_TTL_HOURS")

    # Website audit
    website_audit_concurrency: int = Field(default=5, alias="WEBSITE_AUDIT_CONCURRENCY")
    website_audit_timeout_seconds: int = Field(
        default=15, alias="WEBSITE_AUDIT_TIMEOUT_SECONDS"
    )
    website_audit_max_links: int = Field(default=20, alias="WEBSITE_AUDIT_MAX_LINKS")
    website_audit_slow_threshold_ms: int = Field(
        default=3000, alias="WEBSITE_AUDIT_SLOW_THRESHOLD_MS"
    )
    website_audit_user_agent: str = Field(
        default="LeadResearchBot/1.0 (+contact@yourcompany.example)",
        alias="WEBSITE_AUDIT_USER_AGENT",
    )
    website_audit_use_playwright: bool = Field(
        default=False, alias="WEBSITE_AUDIT_USE_PLAYWRIGHT"
    )

    @property
    def celery_broker_url(self) -> str:
        return f"redis://{self.redis_auth}{self.redis_host}:{self.redis_port}/{self.celery_broker_db}"

    @property
    def celery_result_backend(self) -> str:
        return f"redis://{self.redis_auth}{self.redis_host}:{self.redis_port}/{self.celery_result_db}"


@lru_cache
def get_settings() -> Settings:
    return Settings()


settings = get_settings()
