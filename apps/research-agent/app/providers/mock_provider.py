"""Deterministic mock data so the whole system is demo-able with
RESEARCH_PROVIDER=mock and no SerpApi key (Requirement.md §17).

Each entry is tagged with a `scenario` in raw_source_data purely for
tests/readability — the auditor never looks at that tag, it only ever
reasons from what it actually measures on the live website.
"""
from __future__ import annotations

from app.providers.base import SearchProvider
from app.schemas.business import BusinessDetail, BusinessResult, SearchCriteria

MOCK_BUSINESSES: list[dict] = [
    {
        "scenario": "complete_info",
        "company_name": "บริษัท ตัวอย่างอาหารไทย จำกัด",
        "phone": "02-111-2222",
        "website_url": "https://complete.example.com",
        "address": "123 ถนนสุขุมวิท กรุงเทพมหานคร",
        "province": "กรุงเทพมหานคร",
        "business_type": "food_manufacturing",
        "rating": 4.5,
        "review_count": 120,
        "source_place_id": "mock-place-001",
    },
    {
        "scenario": "missing_phone",
        "company_name": "ร้านขนมไทยโบราณ",
        "phone": None,
        "website_url": "https://nophone.example.com",
        "address": "45 ถนนพระราม 4 กรุงเทพมหานคร",
        "province": "กรุงเทพมหานคร",
        "business_type": "food_retail",
        "rating": 4.1,
        "review_count": 30,
        "source_place_id": "mock-place-002",
    },
    {
        "scenario": "missing_website",
        "company_name": "บริษัท ตัวอย่าง จำกัด",
        "phone": "02-000-0000",
        "website_url": None,
        "address": "สมุทรปราการ",
        "province": "สมุทรปราการ",
        "business_type": "food_manufacturing",
        "rating": 3.8,
        "review_count": 12,
        "source_place_id": "mock-place-003",
    },
    {
        "scenario": "http_only",
        "company_name": "ร้านตัวอย่าง",
        "phone": "081-000-0000",
        "website_url": "http://http-only.example.com",
        "address": "เชียงใหม่",
        "province": "เชียงใหม่",
        "business_type": "retail",
        "rating": 4.0,
        "review_count": 8,
        "source_place_id": "mock-place-004",
    },
    {
        "scenario": "ssl_error",
        "company_name": "บริษัท เอสเอสแอล เอราร์ จำกัด",
        "phone": "02-222-3333",
        "website_url": "https://ssl-error.example.com",
        "address": "ชลบุรี",
        "province": "ชลบุรี",
        "business_type": "manufacturing",
        "rating": 3.5,
        "review_count": 5,
        "source_place_id": "mock-place-005",
    },
    {
        "scenario": "website_down",
        "company_name": "บริษัท เว็บล่ม จำกัด",
        "phone": "02-333-4444",
        "website_url": "https://down.example.com",
        "address": "นนทบุรี",
        "province": "นนทบุรี",
        "business_type": "services",
        "rating": 3.2,
        "review_count": 2,
        "source_place_id": "mock-place-006",
    },
    {
        "scenario": "slow_website",
        "company_name": "บริษัท เว็บช้า จำกัด",
        "phone": "02-444-5555",
        "website_url": "https://slow.example.com",
        "address": "ปทุมธานี",
        "province": "ปทุมธานี",
        "business_type": "manufacturing",
        "rating": 4.2,
        "review_count": 45,
        "source_place_id": "mock-place-007",
    },
    {
        "scenario": "no_viewport",
        "company_name": "บริษัท ไม่มีวิวพอร์ต จำกัด",
        "phone": "02-555-6666",
        "website_url": "https://no-viewport.example.com",
        "address": "นครปฐม",
        "province": "นครปฐม",
        "business_type": "retail",
        "rating": 3.9,
        "review_count": 18,
        "source_place_id": "mock-place-008",
    },
    {
        "scenario": "no_title",
        "company_name": "บริษัท ไม่มีชื่อหน้า จำกัด",
        "phone": "02-666-7777",
        "website_url": "https://no-title.example.com",
        "address": "สมุทรสาคร",
        "province": "สมุทรสาคร",
        "business_type": "manufacturing",
        "rating": 3.6,
        "review_count": 9,
        "source_place_id": "mock-place-009",
    },
    {
        "scenario": "broken_links",
        "company_name": "บริษัท ลิงก์เสีย จำกัด",
        "phone": "02-777-8888",
        "website_url": "https://broken-links.example.com",
        "address": "ระยอง",
        "province": "ระยอง",
        "business_type": "manufacturing",
        "rating": 4.0,
        "review_count": 22,
        "source_place_id": "mock-place-010",
    },
    {
        "scenario": "duplicate_of_010_a",
        "company_name": "บริษัท ลิงก์เสีย จำกัด (สาขา 2)",
        "phone": "02-777-8888",
        "website_url": "https://broken-links.example.com",
        "address": "ระยอง",
        "province": "ระยอง",
        "business_type": "manufacturing",
        "rating": 4.0,
        "review_count": 22,
        "source_place_id": "mock-place-010b",
    },
    {
        "scenario": "no_clear_issue",
        "company_name": "บริษัท เว็บดี จำกัด",
        "phone": "02-888-9999",
        "website_url": "https://healthy.example.com",
        "address": "ขอนแก่น",
        "province": "ขอนแก่น",
        "business_type": "services",
        "rating": 4.8,
        "review_count": 210,
        "source_place_id": "mock-place-011",
    },
    {
        "scenario": "missing_contact_info",
        "company_name": "บริษัท ไม่มีข้อมูลติดต่อ จำกัด",
        "phone": "02-999-0000",
        "website_url": "https://no-contact.example.com",
        "address": "อุดรธานี",
        "province": "อุดรธานี",
        "business_type": "manufacturing",
        "rating": 3.7,
        "review_count": 14,
        "source_place_id": "mock-place-012",
    },
    {
        "scenario": "missing_contact_form",
        "company_name": "บริษัท ไม่มีฟอร์มติดต่อ จำกัด",
        "phone": "02-101-0101",
        "website_url": "https://no-form.example.com",
        "address": "นครราชสีมา",
        "province": "นครราชสีมา",
        "business_type": "manufacturing",
        "rating": 4.1,
        "review_count": 33,
        "source_place_id": "mock-place-013",
    },
    {
        "scenario": "mixed_content",
        "company_name": "บริษัท มิกซ์คอนเทนต์ จำกัด",
        "phone": "02-202-0202",
        "website_url": "https://mixed-content.example.com",
        "address": "ภูเก็ต",
        "province": "ภูเก็ต",
        "business_type": "hospitality",
        "rating": 4.3,
        "review_count": 60,
        "source_place_id": "mock-place-014",
    },
    {
        "scenario": "excessive_redirects",
        "company_name": "บริษัท รีไดเรกต์เยอะ จำกัด",
        "phone": "02-303-0303",
        "website_url": "https://many-redirects.example.com",
        "address": "สงขลา",
        "province": "สงขลา",
        "business_type": "manufacturing",
        "rating": 3.4,
        "review_count": 7,
        "source_place_id": "mock-place-015",
    },
]


class MockProvider(SearchProvider):
    """Used when RESEARCH_PROVIDER=mock — no external API calls at all."""

    def __init__(self, dataset: list[dict] | None = None) -> None:
        self._dataset = dataset if dataset is not None else MOCK_BUSINESSES

    def search_businesses(self, criteria: SearchCriteria) -> list[BusinessResult]:
        results = [self._to_business_result(entry) for entry in self._dataset]

        if not criteria.include_businesses_with_website:
            results = [r for r in results if not r.website_url]
        if not criteria.include_businesses_without_website:
            results = [r for r in results if r.website_url]
        if criteria.minimum_rating is not None:
            results = [r for r in results if (r.rating or 0) >= criteria.minimum_rating]
        if criteria.minimum_review_count is not None:
            results = [
                r for r in results if (r.review_count or 0) >= criteria.minimum_review_count
            ]

        return results[: criteria.maximum_leads]

    def get_business_detail(self, place_reference: str) -> BusinessDetail:
        for entry in self._dataset:
            if entry["source_place_id"] == place_reference:
                return BusinessDetail(**self._entry_fields(entry))

        raise LookupError(f"No mock business found for place reference {place_reference!r}")

    @staticmethod
    def _entry_fields(entry: dict) -> dict:
        return {
            "company_name": entry["company_name"],
            "phone": entry.get("phone"),
            "website_url": entry.get("website_url"),
            "address": entry.get("address"),
            "province": entry.get("province"),
            "district": entry.get("district"),
            "business_type": entry.get("business_type"),
            "rating": entry.get("rating"),
            "review_count": entry.get("review_count"),
            "latitude": entry.get("latitude"),
            "longitude": entry.get("longitude"),
            "source": "mock",
            "source_place_id": entry.get("source_place_id"),
            "raw_source_data": {"scenario": entry["scenario"]},
        }

    def _to_business_result(self, entry: dict) -> BusinessResult:
        return BusinessResult(**self._entry_fields(entry))
