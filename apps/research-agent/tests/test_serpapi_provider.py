import httpx
import pytest
import respx

from app.providers.serpapi_provider import SerpApiGoogleMapsProvider
from app.schemas.business import SearchCriteria
from app.services.quota_tracker import QuotaTracker
from app.services.serpapi_cache import SerpApiCache

BASE_URL = "https://serpapi.com/search"


def _maps_entry(place_id: str, name: str = "Test Co") -> dict:
    return {
        "title": name,
        "place_id": place_id,
        "phone": "02-000-0000",
        "website": "https://example.com",
        "address": "Bangkok",
        "rating": 4.2,
        "reviews": 10,
        "type": "restaurant",
        "gps_coordinates": {"latitude": 13.7, "longitude": 100.5},
    }


def _provider(fake_redis, campaign_id: int = 1, max_requests: int = 50) -> SerpApiGoogleMapsProvider:
    cache = SerpApiCache(fake_redis, ttl_hours=24)
    quota = QuotaTracker(fake_redis, campaign_id=campaign_id, max_requests=max_requests)

    return SerpApiGoogleMapsProvider(api_key="test-key", base_url=BASE_URL, cache=cache, quota=quota)


def test_search_returns_parsed_businesses(fake_redis):
    with respx.mock(assert_all_called=False) as router:
        router.get(BASE_URL).mock(
            return_value=httpx.Response(200, json={"local_results": [_maps_entry("place-1")]})
        )

        provider = _provider(fake_redis)
        results = provider.search_businesses(
            SearchCriteria(business_keyword="ร้านอาหาร", province="กรุงเทพมหานคร", maximum_leads=10)
        )
        provider.close()

    assert len(results) == 1
    assert results[0].company_name == "Test Co"
    assert results[0].source == "serpapi_google_maps"
    assert results[0].source_place_id == "place-1"


def test_identical_request_is_served_from_cache(fake_redis):
    call_count = {"n": 0}

    def handler(request: httpx.Request) -> httpx.Response:
        call_count["n"] += 1
        return httpx.Response(200, json={"local_results": [_maps_entry("place-1")]})

    with respx.mock(assert_all_called=False) as router:
        router.get(BASE_URL).mock(side_effect=handler)

        provider = _provider(fake_redis)
        criteria = SearchCriteria(business_keyword="test", maximum_leads=1)

        provider.search_businesses(criteria)
        first_call_count = call_count["n"]

        provider.search_businesses(criteria)
        provider.close()

    # Second run should hit the cache for the identical query, not the network again.
    assert call_count["n"] == first_call_count
    assert any(log.is_cached for log in provider.usage_logs)


def test_cached_hits_cost_no_quota(fake_redis):
    with respx.mock(assert_all_called=False) as router:
        router.get(BASE_URL).mock(
            return_value=httpx.Response(200, json={"local_results": [_maps_entry("place-1")]})
        )

        provider = _provider(fake_redis, max_requests=1)
        criteria = SearchCriteria(business_keyword="test", maximum_leads=1)

        provider.search_businesses(criteria)  # spends the only unit of quota
        provider.search_businesses(criteria)  # should be a cache hit, no quota needed
        provider.close()

    cached_logs = [log for log in provider.usage_logs if log.is_cached]
    assert len(cached_logs) >= 1


def test_quota_exhaustion_stops_search_without_raising(fake_redis):
    with respx.mock(assert_all_called=False) as router:
        router.get(BASE_URL).mock(
            return_value=httpx.Response(200, json={"local_results": [_maps_entry("place-1")]})
        )

        provider = _provider(fake_redis, max_requests=0)
        results = provider.search_businesses(
            SearchCriteria(business_keyword="test", province="Bangkok", maximum_leads=10)
        )
        provider.close()

    # Quota is exhausted from the start, so maps search yields nothing;
    # small result counts fall through to organic fallback, which also
    # can't spend quota, so the whole search comes back empty rather
    # than raising.
    assert results == []


def test_organic_fallback_triggers_when_maps_has_too_few_results(fake_redis):
    def handler(request: httpx.Request) -> httpx.Response:
        params = dict(request.url.params)
        if params.get("engine") == "google_maps":
            return httpx.Response(200, json={"local_results": []})
        return httpx.Response(
            200,
            json={"organic_results": [{"title": "Some Business", "link": "https://biz.example.com"}]},
        )

    with respx.mock(assert_all_called=False) as router:
        router.get(BASE_URL).mock(side_effect=handler)

        provider = _provider(fake_redis)
        results = provider.search_businesses(
            SearchCriteria(business_keyword="test", province="Bangkok", maximum_leads=5)
        )
        provider.close()

    assert len(results) == 1
    assert results[0].source == "serpapi_organic"
    assert results[0].website_url == "https://biz.example.com"


def test_get_business_detail_returns_parsed_place(fake_redis):
    with respx.mock(assert_all_called=False) as router:
        router.get(BASE_URL).mock(
            return_value=httpx.Response(200, json={"place_results": _maps_entry("place-1", "Detail Co")})
        )

        provider = _provider(fake_redis)
        detail = provider.get_business_detail("place-1")
        provider.close()

    assert detail.company_name == "Detail Co"


def test_get_business_detail_raises_when_not_found(fake_redis):
    with respx.mock(assert_all_called=False) as router:
        router.get(BASE_URL).mock(return_value=httpx.Response(200, json={}))

        provider = _provider(fake_redis)

        with pytest.raises(LookupError):
            provider.get_business_detail("does-not-exist")

        provider.close()


def test_serpapi_error_field_raises_runtime_error(fake_redis):
    with respx.mock(assert_all_called=False) as router:
        router.get(BASE_URL).mock(
            return_value=httpx.Response(200, json={"error": "Invalid API key."})
        )

        provider = _provider(fake_redis)

        with pytest.raises(RuntimeError):
            provider.search_businesses(SearchCriteria(business_keyword="test", maximum_leads=5))

        provider.close()
