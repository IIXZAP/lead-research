from app.services.serpapi_cache import SerpApiCache, compute_request_hash


def test_same_params_produce_same_hash():
    a = compute_request_hash({"engine": "google_maps", "q": "test", "start": 0})
    b = compute_request_hash({"start": 0, "q": "test", "engine": "google_maps"})

    assert a == b


def test_different_params_produce_different_hash():
    a = compute_request_hash({"engine": "google_maps", "q": "test", "start": 0})
    b = compute_request_hash({"engine": "google_maps", "q": "test", "start": 20})

    assert a != b


def test_cache_roundtrip(fake_redis):
    cache = SerpApiCache(fake_redis, ttl_hours=1)
    request_hash = compute_request_hash({"q": "test"})

    assert cache.get(request_hash) is None

    cache.set(request_hash, {"local_results": []})

    assert cache.get(request_hash) == {"local_results": []}
