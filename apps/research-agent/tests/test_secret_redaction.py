import httpx
import respx

from app.providers.serpapi_provider import SerpApiGoogleMapsProvider, _redact_api_key
from app.schemas.business import SearchCriteria
from app.services.quota_tracker import QuotaTracker
from app.services.serpapi_cache import SerpApiCache

BASE_URL = "https://serpapi.com/search"


def test_redact_api_key_strips_the_value():
    text = "error for url 'https://serpapi.com/search?engine=google&api_key=sk-secret-123&q=test'"

    redacted = _redact_api_key(text)

    assert "sk-secret-123" not in redacted
    assert "api_key=***REDACTED***" in redacted


def test_redact_api_key_leaves_other_params_untouched():
    text = "url '?engine=google_maps&api_key=secret&q=coffee+shop'"

    redacted = _redact_api_key(text)

    assert "engine=google_maps" in redacted
    assert "q=coffee+shop" in redacted


def test_http_error_message_never_contains_the_real_api_key(fake_redis):
    with respx.mock(assert_all_called=False) as router:
        router.get(BASE_URL).mock(return_value=httpx.Response(401, json={"error": "Unauthorized"}))

        cache = SerpApiCache(fake_redis, ttl_hours=1)
        quota = QuotaTracker(fake_redis, campaign_id=1, max_requests=10)
        provider = SerpApiGoogleMapsProvider(
            api_key="sk-super-secret-key", base_url=BASE_URL, cache=cache, quota=quota
        )

        try:
            provider.search_businesses(SearchCriteria(business_keyword="test", maximum_leads=1))
        except httpx.HTTPError:
            pass

        provider.close()

    for log in provider.usage_logs:
        if log.error_message:
            assert "sk-super-secret-key" not in log.error_message
