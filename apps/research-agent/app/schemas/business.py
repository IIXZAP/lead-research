"""Schemas for search criteria and raw business results coming out of a
SearchProvider, before normalization/dedup/audit are applied."""
from __future__ import annotations

from typing import Any, Optional

from pydantic import BaseModel, Field, field_validator


class SearchCriteria(BaseModel):
    business_keyword: str
    business_category: Optional[str] = None
    province: Optional[str] = None
    district: Optional[str] = None
    location_text: Optional[str] = None
    latitude: Optional[float] = None
    longitude: Optional[float] = None
    radius_km: int = 10
    maximum_leads: int = 100
    include_businesses_without_website: bool = True
    include_businesses_with_website: bool = True
    minimum_rating: Optional[float] = None
    minimum_review_count: Optional[int] = None
    country: str = "th"
    search_language: str = "th"

    @field_validator("maximum_leads")
    @classmethod
    def cap_maximum_leads(cls, value: int) -> int:
        if value < 1:
            raise ValueError("maximum_leads must be at least 1")
        return value


class BusinessResult(BaseModel):
    """One row as returned directly by a SearchProvider, before this
    service normalizes or deduplicates it."""

    company_name: str
    phone: Optional[str] = None
    website_url: Optional[str] = None
    address: Optional[str] = None
    province: Optional[str] = None
    district: Optional[str] = None
    business_type: Optional[str] = None
    rating: Optional[float] = None
    review_count: Optional[int] = None
    latitude: Optional[float] = None
    longitude: Optional[float] = None
    source: str
    source_place_id: Optional[str] = None
    raw_source_data: dict[str, Any] = Field(default_factory=dict)


class BusinessDetail(BusinessResult):
    """Richer record fetched via a follow-up place-detail lookup, used
    when the search result alone is missing a phone or website."""
