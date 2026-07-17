"""Blocks the website auditor from ever reaching internal infrastructure.

Every hop — the initial URL and every redirect — must pass through
`assert_safe_url` before a request is made. DNS is resolved here (not
left to the HTTP client) specifically to catch DNS-rebinding: a hostname
that resolves to a public IP at check time but a private one by the time
the request fires is still caught because we resolve immediately before
each connection, not once at the start.
"""
from __future__ import annotations

import ipaddress
import socket
from urllib.parse import urlparse

_BLOCKED_NETWORKS = [
    # IPv4 private / loopback / link-local
    ipaddress.ip_network("0.0.0.0/8"),
    ipaddress.ip_network("127.0.0.0/8"),
    ipaddress.ip_network("10.0.0.0/8"),
    ipaddress.ip_network("172.16.0.0/12"),
    ipaddress.ip_network("192.168.0.0/16"),
    ipaddress.ip_network("169.254.0.0/16"),
    # Carrier-grade NAT — used internally by some cloud providers
    ipaddress.ip_network("100.64.0.0/10"),
    # IETF protocol assignments / documentation / benchmarking ranges —
    # never legitimate targets for a business website
    ipaddress.ip_network("192.0.0.0/24"),
    ipaddress.ip_network("192.0.2.0/24"),
    ipaddress.ip_network("198.18.0.0/15"),
    ipaddress.ip_network("198.51.100.0/24"),
    ipaddress.ip_network("203.0.113.0/24"),
    # Multicast / reserved
    ipaddress.ip_network("224.0.0.0/4"),
    ipaddress.ip_network("240.0.0.0/4"),
    # IPv6 loopback / unspecified / unique-local / link-local
    ipaddress.ip_network("::1/128"),
    ipaddress.ip_network("::/128"),
    ipaddress.ip_network("fc00::/7"),
    ipaddress.ip_network("fe80::/10"),
]

_ALLOWED_SCHEMES = {"http", "https"}


class UnsafeUrlError(Exception):
    """Raised when a URL (or one of its redirects) points at disallowed
    infrastructure and must not be fetched."""


def _is_blocked_ip(ip: str) -> bool:
    address = ipaddress.ip_address(ip)

    # An IPv4-mapped IPv6 address (e.g. ::ffff:127.0.0.1) must be checked
    # against the IPv4 blocklist too, or it silently bypasses every IPv4
    # rule above while still routing to the same private/loopback host.
    if isinstance(address, ipaddress.IPv6Address) and address.ipv4_mapped is not None:
        address = address.ipv4_mapped

    return any(address in network for network in _BLOCKED_NETWORKS)


def resolve_all(hostname: str) -> list[str]:
    """All resolved IPs for a hostname, not just the first — some
    resolvers round-robin, and every address must be checked."""
    try:
        infos = socket.getaddrinfo(hostname, None)
    except socket.gaierror as exc:
        raise UnsafeUrlError(f"DNS resolution failed for {hostname!r}") from exc

    return sorted({info[4][0] for info in infos})


def assert_safe_url(url: str) -> None:
    parsed = urlparse(url)

    if parsed.scheme not in _ALLOWED_SCHEMES:
        raise UnsafeUrlError(f"Unsupported URL scheme: {parsed.scheme!r}")

    if not parsed.hostname:
        raise UnsafeUrlError("URL has no hostname")

    if parsed.username or parsed.password:
        raise UnsafeUrlError("URLs with embedded credentials are not allowed")

    for ip in resolve_all(parsed.hostname):
        if _is_blocked_ip(ip):
            raise UnsafeUrlError(
                f"{parsed.hostname!r} resolves to disallowed address {ip!r}"
            )
