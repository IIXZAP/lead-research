"""Interface every search provider (SerpApi, Mock, future providers) implements.

Kept as a runtime-checkable Protocol rather than an ABC so a mock object in
tests doesn't need to formally subclass anything — it just needs the two
methods.
"""
from __future__ import annotations

from typing import Protocol, runtime_checkable

from app.schemas.business import BusinessDetail, BusinessResult, SearchCriteria


@runtime_checkable
class SearchProvider(Protocol):
    def search_businesses(self, criteria: SearchCriteria) -> list[BusinessResult]:
        ...

    def get_business_detail(self, place_reference: str) -> BusinessDetail:
        ...
