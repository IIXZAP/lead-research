"""Duplicate detection, in the exact priority order from Requirement.md §7:

1. source_place_id match
2. normalized_phone match (non-null)
3. normalized_domain match (non-null)
4. normalized_company_name + address similarity
5. fuzzy matching, only trusted at high confidence

Low-confidence fuzzy matches are never auto-merged — they're kept as
separate leads but flagged `potential_duplicate` for a human to review,
per the requirement's explicit ban on merging at low confidence.
"""
from __future__ import annotations

from dataclasses import dataclass, field
from difflib import SequenceMatcher

from app.schemas.business import BusinessResult
from app.services.normalizer import normalize_company_name, normalize_domain, normalize_phone

FUZZY_MERGE_THRESHOLD = 0.92  # confident enough to treat as the same business
FUZZY_FLAG_THRESHOLD = 0.80  # similar enough to warn a human, not similar enough to merge


@dataclass
class DedupEntry:
    business: BusinessResult
    normalized_company_name: str
    normalized_phone: str | None
    normalized_domain: str | None
    potential_duplicate: bool = False
    matched_reason: str | None = None


@dataclass
class DeduplicationResult:
    unique: list[DedupEntry] = field(default_factory=list)
    dropped: list[dict] = field(default_factory=list)


def _similarity(a: str, b: str) -> float:
    if not a or not b:
        return 0.0
    return SequenceMatcher(None, a, b).ratio()


def deduplicate(businesses: list[BusinessResult]) -> DeduplicationResult:
    result = DeduplicationResult()

    seen_place_ids: dict[str, int] = {}
    seen_phones: dict[str, int] = {}
    seen_domains: dict[str, int] = {}

    for business in businesses:
        norm_name = normalize_company_name(business.company_name)
        norm_phone = normalize_phone(business.phone)
        norm_domain = normalize_domain(business.website_url)

        place_id = business.source_place_id
        matched_index: int | None = None
        matched_reason: str | None = None

        if place_id and place_id in seen_place_ids:
            matched_index = seen_place_ids[place_id]
            matched_reason = "source_place_id"
        elif norm_phone and norm_phone in seen_phones:
            matched_index = seen_phones[norm_phone]
            matched_reason = "normalized_phone"
        elif norm_domain and norm_domain in seen_domains:
            matched_index = seen_domains[norm_domain]
            matched_reason = "normalized_domain"

        if matched_index is not None:
            result.dropped.append(
                {
                    "company_name": business.company_name,
                    "matched_reason": matched_reason,
                    "matched_index": matched_index,
                }
            )
            continue

        # Fuzzy pass against everything accepted so far.
        potential_duplicate = False
        fuzzy_reason = None
        for index, existing in enumerate(result.unique):
            name_similarity = _similarity(norm_name, existing.normalized_company_name)
            address_similarity = _similarity(
                business.address or "", existing.business.address or ""
            )
            combined = (name_similarity + address_similarity) / 2

            if combined >= FUZZY_MERGE_THRESHOLD:
                matched_index = index
                fuzzy_reason = "fuzzy_high_confidence"
                break
            if combined >= FUZZY_FLAG_THRESHOLD:
                potential_duplicate = True
                fuzzy_reason = "fuzzy_low_confidence"

        if matched_index is not None and fuzzy_reason == "fuzzy_high_confidence":
            result.dropped.append(
                {
                    "company_name": business.company_name,
                    "matched_reason": fuzzy_reason,
                    "matched_index": matched_index,
                }
            )
            continue

        entry = DedupEntry(
            business=business,
            normalized_company_name=norm_name,
            normalized_phone=norm_phone,
            normalized_domain=norm_domain,
            potential_duplicate=potential_duplicate,
            matched_reason=fuzzy_reason if potential_duplicate else None,
        )
        result.unique.append(entry)

        new_index = len(result.unique) - 1
        if place_id:
            seen_place_ids[place_id] = new_index
        if norm_phone:
            seen_phones[norm_phone] = new_index
        if norm_domain:
            seen_domains[norm_domain] = new_index

    return result
