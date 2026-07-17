import httpx
import pytest
import respx

from app.services.website_auditor import WebsiteAuditor


@pytest.fixture(autouse=True)
def _bypass_dns(monkeypatch):
    """Tests use fictional *.example.com hosts that don't really resolve;
    the SSRF check itself is covered separately in test_ssrf.py, so here
    we only care that the auditor calls it and reacts to what httpx
    returns."""
    monkeypatch.setattr("app.services.website_auditor.assert_safe_url", lambda url: None)


def test_healthy_site_reports_no_missing_fields():
    with respx.mock(assert_all_called=False) as router:
        router.get("https://healthy.example.com").mock(
            return_value=httpx.Response(
                200,
                html=(
                    "<html><head><title>Good</title>"
                    '<meta name="description" content="x">'
                    '<meta name="viewport" content="width=device-width"></head>'
                    '<body><form><input type="email"><textarea></textarea></form>'
                    "<p>Call 02-123-4567</p></body></html>"
                ),
            )
        )
        with WebsiteAuditor() as auditor:
            metrics = auditor.audit("https://healthy.example.com")

    assert metrics.http_status == 200
    assert metrics.title_found is True
    assert metrics.mobile_viewport_found is True
    assert metrics.contact_form_found is True
    assert metrics.contact_information_found is True


def test_http_client_error_status_is_captured():
    with respx.mock(assert_all_called=False) as router:
        router.get("https://notfound.example.com").mock(return_value=httpx.Response(404, html="<html></html>"))
        with WebsiteAuditor() as auditor:
            metrics = auditor.audit("https://notfound.example.com")

    assert metrics.http_status == 404


def test_http_server_error_status_is_captured():
    with respx.mock(assert_all_called=False) as router:
        router.get("https://broken.example.com").mock(return_value=httpx.Response(500, html="<html></html>"))
        with WebsiteAuditor() as auditor:
            metrics = auditor.audit("https://broken.example.com")

    assert metrics.http_status == 500


def test_connection_timeout_is_captured():
    with respx.mock(assert_all_called=False) as router:
        router.get("https://slow.example.com").mock(side_effect=httpx.ConnectTimeout("timed out"))
        with WebsiteAuditor() as auditor:
            metrics = auditor.audit("https://slow.example.com")

    assert metrics.connection_error == "CONNECTION_TIMEOUT"


def test_missing_viewport_is_detected():
    with respx.mock(assert_all_called=False) as router:
        router.get("https://no-viewport.example.com").mock(
            return_value=httpx.Response(200, html="<html><head><title>x</title></head><body></body></html>")
        )
        with WebsiteAuditor() as auditor:
            metrics = auditor.audit("https://no-viewport.example.com")

    assert metrics.mobile_viewport_found is False


def test_missing_title_is_detected():
    with respx.mock(assert_all_called=False) as router:
        router.get("https://no-title.example.com").mock(
            return_value=httpx.Response(200, html="<html><head></head><body>hi</body></html>")
        )
        with WebsiteAuditor() as auditor:
            metrics = auditor.audit("https://no-title.example.com")

    assert metrics.title_found is False


def test_mixed_content_is_detected_on_https_page():
    with respx.mock(assert_all_called=False) as router:
        router.get("https://mixed.example.com").mock(
            return_value=httpx.Response(
                200,
                html='<html><head><title>x</title></head><body><img src="http://insecure.example.com/a.png"></body></html>',
            )
        )
        with WebsiteAuditor() as auditor:
            metrics = auditor.audit("https://mixed.example.com")

    assert metrics.mixed_content_found is True


def test_broken_internal_links_are_counted():
    with respx.mock(assert_all_called=False) as router:
        router.get("https://links.example.com").mock(
            return_value=httpx.Response(
                200,
                html=(
                    '<html><head><title>x</title></head><body>'
                    '<a href="https://links.example.com/ok">ok</a>'
                    '<a href="https://links.example.com/missing">missing</a>'
                    "</body></html>"
                ),
            )
        )
        router.head("https://links.example.com/ok").mock(return_value=httpx.Response(200))
        router.head("https://links.example.com/missing").mock(return_value=httpx.Response(404))

        with WebsiteAuditor() as auditor:
            metrics = auditor.audit("https://links.example.com")

    assert metrics.broken_link_count == 1
    assert metrics.checked_link_count == 2


def test_website_not_found_when_url_is_none():
    with WebsiteAuditor() as auditor:
        metrics = auditor.audit(None)  # type: ignore[arg-type]

    assert metrics.website_url is None
