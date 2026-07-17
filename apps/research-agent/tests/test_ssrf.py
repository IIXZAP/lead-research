import pytest

from app.security.ssrf import UnsafeUrlError, assert_safe_url


def test_allows_public_https_url():
    assert_safe_url("https://example.com")


@pytest.mark.parametrize(
    "url",
    [
        "http://127.0.0.1",
        "http://localhost",
        "http://169.254.169.254/latest/meta-data/",
        "http://10.0.0.5",
        "http://192.168.1.1",
        "http://0.0.0.0",
        "http://100.64.0.1",
        "http://192.0.2.1",
        "http://198.51.100.1",
        "http://203.0.113.1",
        "http://224.0.0.1",
        "http://240.0.0.1",
    ],
)
def test_blocks_private_and_metadata_addresses(url):
    with pytest.raises(UnsafeUrlError):
        assert_safe_url(url)


def test_blocks_ipv4_mapped_ipv6_bypass_of_loopback(monkeypatch):
    monkeypatch.setattr("app.security.ssrf.resolve_all", lambda hostname: ["::ffff:127.0.0.1"])

    with pytest.raises(UnsafeUrlError):
        assert_safe_url("https://evil.example.com")


def test_blocks_ipv6_unspecified_address(monkeypatch):
    monkeypatch.setattr("app.security.ssrf.resolve_all", lambda hostname: ["::"])

    with pytest.raises(UnsafeUrlError):
        assert_safe_url("https://evil.example.com")


def test_blocks_disallowed_scheme():
    with pytest.raises(UnsafeUrlError):
        assert_safe_url("ftp://example.com")


def test_blocks_url_with_embedded_credentials():
    with pytest.raises(UnsafeUrlError):
        assert_safe_url("https://user:pass@example.com")
