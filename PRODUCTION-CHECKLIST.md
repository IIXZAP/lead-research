# Production Readiness Checklist

Phase 7 deliverable. This is a review pass, not a rebuild — most of the hard security work
(HMAC, SSRF protection, policy-based authorization, idempotency) already happened in
Phases 3–5. This document is what to actually check/change before this leaves "demo-able
locally" and becomes "safe to point at the real internet."

## Before first deploy

- [ ] **Rotate every secret.** `INTERNAL_SHARED_SECRET` and `APP_KEY` in this repo's
  `.env.example` are placeholders (`change-me-in-production`) — generate real ones:
  ```bash
  openssl rand -hex 32   # for INTERNAL_SHARED_SECRET, set identically in BOTH .env files
  php artisan key:generate  # for APP_KEY, run inside the laravel container
  ```
- [ ] **`APP_ENV=production`, `APP_DEBUG=false`** in `apps/web/.env`. With debug on, Laravel
  renders full stack traces (including config values) to any visitor who triggers a 500 —
  this is the single most common real-world Laravel leak.
- [ ] **`SESSION_SECURE_COOKIE=true`** in `apps/web/.env`, and confirm the app is only ever
  reached over HTTPS (see nginx section below). A secure-flagged cookie sent over plain
  HTTP silently fails to persist, so don't set this until HTTPS is actually terminated
  somewhere in front of the app.
- [ ] **TLS termination.** `infrastructure/nginx/default.conf` ships commented-out HTTPS
  server blocks — either uncomment and mount real certificates there, or terminate TLS at
  an upstream load balancer/ingress. Either way, nothing should reach the app over plain
  HTTP from outside the private network.
- [ ] **`CORS_ALLOWED_ORIGINS`** — leave empty unless a separately-hosted frontend calls
  `/api/campaigns`. Never set it to `*`; `config/cors.php` has `supports_credentials: true`,
  and a wildcard origin combined with credentials is a real vulnerability, not a theoretical
  one (`tests/Unit/CorsConfigTest.php` asserts this never regresses).
- [ ] **BCRYPT_ROUNDS** — `.env.example` ships `12` (the tests override this to `4` for
  speed only; never carry `4` into production).

## Secrets management

- [ ] `.env` files are in `.gitignore` already — confirm neither `apps/web/.env` nor
  `apps/research-agent/.env` was ever actually committed (`git log --all --full-history --
  apps/web/.env`).
- [ ] Prefer injecting secrets via your platform's secret manager (Docker/Kubernetes
  secrets, AWS Secrets Manager, etc.) over plain `.env` files on disk in production.
- [ ] `SERPAPI_API_KEY` — this project already redacts it from logs and error messages
  (`app/providers/serpapi_provider.py::_redact_api_key`, covered by
  `tests/test_secret_redaction.py`) but double-check any external log aggregator you add
  doesn't capture raw request URLs before they reach the app.

## Network / SSRF

- [ ] The website auditor's SSRF blocklist (`app/security/ssrf.py`) covers private/loopback/
  link-local ranges, cloud metadata IPs, CGNAT, documentation/benchmark ranges, and
  IPv4-mapped IPv6 bypass attempts — see `tests/test_ssrf.py`. If you deploy inside a VPC
  with additional internal ranges (e.g. a non-RFC1918 internal CIDR), add them to
  `_BLOCKED_NETWORKS`.
- [ ] The internal API (`internal.signed` middleware + `throttle:internal-api`, 120
  req/min/IP) is reachable only from `python-api` on the Docker network in this compose
  file. If you deploy the two services on separate hosts, put the internal API behind a
  firewall/security-group rule too — HMAC auth is the primary control, network isolation
  is the second layer, not a replacement for it.
- [ ] CSV export is rate-limited to 10/min/user (`campaign-export` limiter) since it runs
  an unpaginated query — revisit the limit if campaigns routinely have tens of thousands
  of leads.

## Dependency hygiene

- [ ] `make audit` runs `composer audit` and `pip-audit` against both dependency trees.
  Run it before every deploy, not just once. `composer.json` already sets
  `policy.advisories.block: false` (Phase 3 fix) — the audit report is informational, not a
  blocker, so don't skip actually reading it.
- [x] **Fixed during this Phase 7 pass**: `pip-audit` flagged `fastapi==0.115.6` /
  `starlette==0.41.3` (a live runtime dependency serving every request), plus dev-only
  `pytest`/`python-dotenv`. Upgraded to `fastapi==0.139.0` (pulls in a patched Starlette),
  `pytest==9.1.1`, `pytest-asyncio==1.4.0` (0.24.0 pins `pytest<9`, had to bump together),
  `pytest-httpx==0.36.2` (0.35.0 pins `pytest==8.*`, same issue), `python-dotenv==1.2.2`.
  Verified in a clean virtualenv scoped to only `requirements.txt` (not this sandbox's
  broader environment, which had unrelated packages muddying the signal) — all 101 tests
  pass, and a follow-up `pip-audit` in that clean venv reports zero findings.
- [ ] `pip-audit` also flagged `pip` itself (25.0.1, several advisories, fixed in
  25.3/26.0/26.1.2) — this is the *build-time* package manager baked into the
  `python:3.12-slim` base image, not something `requirements.txt` controls, and it never
  processes untrusted input while the service is running. Bump it by changing the base
  image tag in `infrastructure/python/Dockerfile` when convenient; it's not urgent the way
  the Starlette finding was.
- [ ] **Known accepted risk (as of this writing): `laravel/framework` on the `^11.31` line
  has two open advisories with no 11.x patch available** —
  [GHSA-crmm-hgp2-wgrp](https://github.com/advisories/GHSA-crmm-hgp2-wgrp) (temporary
  signed URL path confusion, fixed only in 12.61.1+/13.12.0+) and
  [GHSA-5vg9-5847-vvmq](https://github.com/laravel/framework/security/advisories/GHSA-5vg9-5847-vvmq)
  / CVE-2026-48019 (CRLF injection via the `email` validation rule when an app sends mail
  to a user-supplied address, fixed only in 12.60.0+/13.10.0+). Neither is exploitable in
  this codebase today: nothing here uses `Route::signed()`/`URL::temporarySignedRoute()`,
  and there is no outbound-email feature at all (`MAIL_MAILER=log`, no password reset, no
  contact form). **This changes the moment either of those features gets added** — upgrade
  past Laravel 11 (to 12.60.0+/13.10.0+ at minimum) before shipping a password-reset,
  email-verification, invite-link, or any other feature that emails a user-supplied
  address or issues a signed URL.

## Accounts & access

- [ ] There is intentionally **no self-service registration** in this app — every user
  (`admin` / `sales` / `viewer`) is created via `php artisan db:seed` or `tinker` today.
  If you need a UI for provisioning users, that's new scope beyond what's built here —
  don't assume it exists.
- [ ] Login is rate-limited (5 attempts, `app/Http/Requests/Auth/LoginRequest.php`) — this
  is a per-email+IP limiter, not a global one; verify it still fits your threat model at
  scale.

## Observability

- [ ] `docker-compose.yml`'s healthchecks (`GET /api/health` for Laravel,
  `GET /internal/v1/health` for Python) are wired for Docker/orchestrator-level restarts —
  point your actual uptime monitoring at the same endpoints.
- [ ] Laravel logs to `storage/logs/laravel.log` by default (`LOG_CHANNEL=stack`) — ship
  this to a real log aggregator in production; a container that gets recreated loses
  everything in that file otherwise.
- [ ] `api_usage_logs` (SerpApi credit spend) and `job_logs` (pipeline errors) are already
  persisted to MySQL — build alerting on top of these (e.g. "campaign failed_items > 0")
  rather than only checking the UI.

## What's explicitly out of scope for this checklist

- Load testing / capacity planning — this repo has never been run under production traffic.
- A formal penetration test — the security work here (HMAC, SSRF, policy authorization,
  rate limiting, header hardening) is solid engineering practice, not a substitute for an
  actual third-party security review before handling real customer data at scale.
- Backup/restore runbooks for MySQL — `docker-compose.yml`'s `mysql-data` volume persists
  across container restarts, but you still need an actual backup strategy (mysqldump on a
  schedule, at minimum) before this holds data you can't afford to lose.
