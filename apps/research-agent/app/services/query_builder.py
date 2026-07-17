"""Turns a SearchCriteria into concrete search query strings (Requirement.md §4).

Deliberately simple, deterministic string templates rather than an LLM
call — the requirement's own examples are template-shaped
("{keyword} {province}", "{english} {province}", "{keyword} ใกล้ {province}"),
so a translation table covers the common business keywords used in the
demo/mock dataset. Anything not in the table is skipped for the English
variant rather than guessed.
"""
from __future__ import annotations

from app.schemas.business import SearchCriteria

_ENGLISH_KEYWORD_MAP: dict[str, str] = {
    "โรงงานผลิตอาหาร": "food manufacturing company",
    "โรงงานอาหาร": "food factory",
    "ร้านอาหาร": "restaurant",
    "คลินิกความงาม": "beauty clinic",
    "บริษัทขนส่ง": "logistics company",
}


def build_queries(criteria: SearchCriteria) -> list[str]:
    location = criteria.province or criteria.location_text or ""
    keyword = criteria.business_keyword.strip()

    queries: list[str] = []

    if location:
        queries.append(f"{keyword} {location}".strip())
    else:
        queries.append(keyword)

    english_keyword = _ENGLISH_KEYWORD_MAP.get(keyword)
    if english_keyword and location:
        queries.append(f"{english_keyword} {location}".strip())

    if location:
        queries.append(f"{keyword} ใกล้ {location}".strip())

    return deduplicate_queries(queries)


def deduplicate_queries(queries: list[str]) -> list[str]:
    """Preserves order while dropping exact and case-insensitive repeats —
    Requirement.md is explicit that a campaign must never issue the same
    query to SerpApi twice."""
    seen: set[str] = set()
    unique: list[str] = []

    for query in queries:
        key = query.strip().lower()
        if key and key not in seen:
            seen.add(key)
            unique.append(query.strip())

    return unique
