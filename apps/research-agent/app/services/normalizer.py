"""Normalization rules from Requirement.md §7.

Every function here is pure and side-effect free so it's trivial to unit
test in isolation from the rest of the pipeline.
"""
from __future__ import annotations

import re
from urllib.parse import urlparse

# Thai legal-entity words stripped only from the *normalized* company name
# used for comparison — never from the display value in `company_name`.
_LEGAL_ENTITY_WORDS = ["บริษัท", "หจก.", "ห้างหุ้นส่วนจำกัด", "จำกัด (มหาชน)", "จำกัด"]

_WHITESPACE_RE = re.compile(r"\s+")
_NON_ESSENTIAL_PUNCTUATION_RE = re.compile(r"[.,()\"'“”‘’]")


def normalize_company_name(company_name: str) -> str:
    """Lowercased, whitespace-collapsed, legal-entity-stripped name used
    purely for duplicate comparison. The original `company_name` value
    shown to users is never touched by this."""
    value = company_name.strip()
    value = _WHITESPACE_RE.sub(" ", value)
    value = value.lower()
    value = _NON_ESSENTIAL_PUNCTUATION_RE.sub("", value)

    for word in _LEGAL_ENTITY_WORDS:
        value = value.replace(word.lower(), "")

    value = _WHITESPACE_RE.sub(" ", value).strip()

    return value


def normalize_phone(phone: str | None) -> str | None:
    """Digits-only representation for comparison. +66 is converted to a
    leading 0 so it compares equal to the local format. Never guesses or
    completes a partial number — an incomplete number normalizes to
    whatever digits are actually present, it is not padded or fixed."""
    if not phone:
        return None

    digits_and_plus = re.sub(r"[^\d+]", "", phone)

    if digits_and_plus.startswith("+66"):
        return "0" + digits_and_plus[3:]
    if digits_and_plus.startswith("66") and len(digits_and_plus) > 9:
        # A bare 66 prefix (no +) meeting a plausible Thai number length.
        return "0" + digits_and_plus[2:]

    return re.sub(r"\D", "", digits_and_plus) or None


def normalize_domain(url: str | None) -> str | None:
    """Lowercased host, no protocol, no leading www., no trailing slash."""
    if not url:
        return None

    candidate = url.strip()
    if "//" not in candidate:
        candidate = f"//{candidate}"

    host = urlparse(candidate).netloc or urlparse(candidate).path
    host = host.lower().strip("/")

    if host.startswith("www."):
        host = host[len("www."):]

    return host or None
