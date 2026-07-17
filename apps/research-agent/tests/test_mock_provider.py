import pytest

from app.providers.mock_provider import MockProvider
from app.schemas.business import SearchCriteria


def test_mock_dataset_has_at_least_fifteen_businesses():
    provider = MockProvider()
    results = provider.search_businesses(SearchCriteria(business_keyword="x", maximum_leads=1000))

    assert len(results) >= 15


def test_mock_dataset_covers_missing_phone_and_missing_website():
    provider = MockProvider()
    results = provider.search_businesses(SearchCriteria(business_keyword="x", maximum_leads=1000))

    assert any(r.phone is None for r in results)
    assert any(r.website_url is None for r in results)


def test_maximum_leads_is_respected():
    provider = MockProvider()
    results = provider.search_businesses(SearchCriteria(business_keyword="x", maximum_leads=3))

    assert len(results) == 3


def test_exclude_businesses_without_website():
    provider = MockProvider()
    criteria = SearchCriteria(
        business_keyword="x", maximum_leads=1000, include_businesses_without_website=False
    )

    results = provider.search_businesses(criteria)

    assert all(r.website_url for r in results)


def test_get_business_detail_returns_matching_entry():
    provider = MockProvider()
    detail = provider.get_business_detail("mock-place-001")

    assert detail.company_name == "บริษัท ตัวอย่างอาหารไทย จำกัด"


def test_get_business_detail_raises_for_unknown_reference():
    provider = MockProvider()

    with pytest.raises(LookupError):
        provider.get_business_detail("does-not-exist")
