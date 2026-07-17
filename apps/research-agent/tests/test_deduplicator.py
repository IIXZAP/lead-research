from app.schemas.business import BusinessResult
from app.services.deduplicator import deduplicate


def _business(**overrides) -> BusinessResult:
    defaults = dict(
        company_name="Test Co",
        phone="02-111-1111",
        website_url="https://test.example.com",
        address="Bangkok",
        source="mock",
        source_place_id="place-1",
    )
    defaults.update(overrides)
    return BusinessResult(**defaults)


def test_same_source_place_id_is_deduplicated():
    a = _business(source_place_id="place-1")
    b = _business(company_name="Test Co 2", source_place_id="place-1")

    result = deduplicate([a, b])

    assert len(result.unique) == 1
    assert result.dropped[0]["matched_reason"] == "source_place_id"


def test_same_normalized_phone_is_deduplicated():
    a = _business(source_place_id="place-1", phone="02-111-1111")
    b = _business(source_place_id="place-2", phone="02-111-1111")

    result = deduplicate([a, b])

    assert len(result.unique) == 1
    assert result.dropped[0]["matched_reason"] == "normalized_phone"


def test_same_normalized_domain_is_deduplicated():
    a = _business(source_place_id="place-1", phone=None, website_url="https://www.test.example.com/")
    b = _business(source_place_id="place-2", phone=None, website_url="https://test.example.com")

    result = deduplicate([a, b])

    assert len(result.unique) == 1
    assert result.dropped[0]["matched_reason"] == "normalized_domain"


def test_businesses_without_phone_or_domain_never_collide():
    a = _business(source_place_id="place-1", phone=None, website_url=None, company_name="Alpha Co")
    b = _business(source_place_id="place-2", phone=None, website_url=None, company_name="Beta Co")

    result = deduplicate([a, b])

    assert len(result.unique) == 2
    assert result.dropped == []


def test_low_confidence_fuzzy_match_is_flagged_not_merged():
    a = _business(
        source_place_id="place-1", phone=None, website_url=None,
        company_name="Siam Coffee Shop", address="123 Sukhumvit Road Bangkok",
    )
    b = _business(
        source_place_id="place-2", phone=None, website_url=None,
        company_name="Siam Coffee Bar", address="123 Sukhumvit Road Bangkok",
    )

    result = deduplicate([a, b])

    assert len(result.unique) == 2
    assert any(entry.potential_duplicate for entry in result.unique)


def test_high_confidence_fuzzy_match_is_merged():
    a = _business(
        source_place_id="place-1", phone=None, website_url=None,
        company_name="Siam Coffee Shop Co Ltd", address="123 Sukhumvit Road Bangkok",
    )
    b = _business(
        source_place_id="place-2", phone=None, website_url=None,
        company_name="Siam Coffee Shop Co Ltd", address="123 Sukhumvit Road Bangkok",
    )

    result = deduplicate([a, b])

    assert len(result.unique) == 1
