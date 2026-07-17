"""Fetches and measures a single website (Requirement.md §8).

Only ever collects deterministic facts (status codes, timings, presence
of tags) — it never infers or judges quality. That judgment happens in
issue_classifier.py, which is the only thing allowed to turn these
metrics into an Issue.

Playwright is intentionally not used here — it's opt-in
(WEBSITE_AUDIT_USE_PLAYWRIGHT) and lives in a separate module so plain
HTTPX + BeautifulSoup stays the default, low-resource path.
"""
from __future__ import annotations

import re
import time
from urllib.parse import urljoin, urlparse

import httpx
from bs4 import BeautifulSoup

from app.schemas.audit import AuditMetrics
from app.security.ssrf import UnsafeUrlError, assert_safe_url

_CONTACT_PATTERN = re.compile(
    r"(\+?\d[\d\-\s]{7,}\d)|([\w.+-]+@[\w-]+\.[\w.-]+)"
)


class WebsiteAuditor:
    def __init__(
        self,
        *,
        timeout_seconds: int = 15,
        max_redirects: int = 5,
        max_response_bytes: int = 5_000_000,
        max_links: int = 20,
        slow_threshold_ms: int = 3000,
        user_agent: str = "LeadResearchBot/1.0",
        client: httpx.Client | None = None,
    ) -> None:
        self._timeout_seconds = timeout_seconds
        self._max_redirects = max_redirects
        self._max_response_bytes = max_response_bytes
        self._max_links = max_links
        self._slow_threshold_ms = slow_threshold_ms
        self._user_agent = user_agent
        self._owns_client = client is None
        self._client = client or httpx.Client(
            timeout=httpx.Timeout(timeout_seconds),
            follow_redirects=False,
            headers={"User-Agent": user_agent},
        )

    def close(self) -> None:
        if self._owns_client:
            self._client.close()

    def __enter__(self) -> "WebsiteAuditor":
        return self

    def __exit__(self, *exc_info: object) -> None:
        self.close()

    def audit(self, url: str | None) -> AuditMetrics:
        if not url:
            return AuditMetrics(website_url=None)

        redirect_count = 0
        current_url = url

        try:
            assert_safe_url(current_url)
        except UnsafeUrlError as exc:
            return AuditMetrics(website_url=url, connection_error=str(exc), dns_resolved=False)

        start = time.monotonic()
        response: httpx.Response | None = None

        try:
            while True:
                response = self._client.get(current_url)

                if response.is_redirect:
                    redirect_count += 1
                    if redirect_count > self._max_redirects:
                        return AuditMetrics(
                            website_url=url,
                            final_url=current_url,
                            redirect_count=redirect_count,
                            connection_error="EXCESSIVE_REDIRECTS",
                        )

                    next_url = urljoin(current_url, response.headers.get("location", ""))
                    assert_safe_url(next_url)
                    current_url = next_url
                    continue

                break
        except UnsafeUrlError as exc:
            return AuditMetrics(
                website_url=url,
                final_url=current_url,
                redirect_count=redirect_count,
                connection_error=str(exc),
            )
        except httpx.ConnectTimeout:
            return AuditMetrics(
                website_url=url, redirect_count=redirect_count, connection_error="CONNECTION_TIMEOUT"
            )
        except httpx.ReadTimeout:
            return AuditMetrics(
                website_url=url, redirect_count=redirect_count, connection_error="CONNECTION_TIMEOUT"
            )
        except httpx.ConnectError as exc:
            error_code = "SSL_INVALID" if "certificate" in str(exc).lower() else "DNS_ERROR"
            return AuditMetrics(website_url=url, redirect_count=redirect_count, connection_error=error_code)

        response_time_ms = int((time.monotonic() - start) * 1000)
        body = response.content[: self._max_response_bytes]
        final_url = current_url
        https_enabled = urlparse(final_url).scheme == "https"

        soup = BeautifulSoup(body, "html.parser")

        title_found = bool(soup.title and soup.title.text.strip())
        meta_description_found = bool(soup.find("meta", attrs={"name": "description"}))
        viewport_found = bool(soup.find("meta", attrs={"name": "viewport"}))
        contact_information_found = bool(_CONTACT_PATTERN.search(soup.get_text(" ")))
        contact_form_found = self._has_contact_form(soup)
        mixed_content_found = https_enabled and self._has_mixed_content(soup)
        broken_link_count, checked_link_count = self._check_links(soup, final_url)

        return AuditMetrics(
            website_url=url,
            final_url=final_url,
            http_status=response.status_code,
            https_enabled=https_enabled,
            ssl_valid=True if https_enabled else None,
            response_time_ms=response_time_ms,
            page_size_bytes=len(body),
            mobile_viewport_found=viewport_found,
            title_found=title_found,
            meta_description_found=meta_description_found,
            contact_information_found=contact_information_found,
            contact_form_found=contact_form_found,
            broken_link_count=broken_link_count,
            redirect_count=redirect_count,
            mixed_content_found=mixed_content_found,
            checked_link_count=checked_link_count,
        )

    @staticmethod
    def _has_contact_form(soup: BeautifulSoup) -> bool:
        for form in soup.find_all("form"):
            if form.find("input", attrs={"type": "email"}) or form.find("textarea"):
                return True
        return False

    @staticmethod
    def _has_mixed_content(soup: BeautifulSoup) -> bool:
        for tag, attr in (("img", "src"), ("script", "src"), ("link", "href")):
            for el in soup.find_all(tag):
                value = el.get(attr, "")
                if value.startswith("http://"):
                    return True
        return False

    def _check_links(self, soup: BeautifulSoup, base_url: str) -> tuple[int, int]:
        base_host = urlparse(base_url).netloc
        internal_links: list[str] = []

        for a in soup.find_all("a", href=True):
            href = urljoin(base_url, a["href"])
            parsed = urlparse(href)
            if parsed.scheme in ("http", "https") and parsed.netloc == base_host:
                internal_links.append(href)
            if len(internal_links) >= self._max_links:
                break

        broken = 0
        checked = 0

        for link in internal_links:
            try:
                assert_safe_url(link)
                head_response = self._client.head(link)
                checked += 1
                if head_response.status_code >= 400:
                    broken += 1
            except (UnsafeUrlError, httpx.HTTPError):
                checked += 1
                broken += 1

        return broken, checked
