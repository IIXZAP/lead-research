# Lead Research Platform

B2B lead-generation system: a Laravel app for campaign management and the lead UI, paired
with a Python (FastAPI + Celery) research service that searches SerpApi and audits
company websites. See `PHASE-1-ARCHITECTURE.md` (delivered separately) for the full
architecture, API contract, and ER diagram.

**Current state: Phase 7 — Security & production readiness.** All 6 functional phases are
complete and this pass hardens what's there rather than adding new features. See
[`PRODUCTION-CHECKLIST.md`](./PRODUCTION-CHECKLIST.md) for the full pre-deploy review.

## What exists right now

Everything from Phases 1–6 (see git history / earlier phase notes), plus this pass's
security hardening:

- **Secret redaction**: SerpApi's API key is stripped from every error message and log
  line before it's stored or logged (`app/providers/serpapi_provider.py::_redact_api_key`)
  — `httpx` embeds the full request URL, including `?api_key=...`, in its exception
  messages by default, which would otherwise have leaked into `api_usage_logs` and
  application logs.
- **Expanded SSRF blocklist**: beyond the private/loopback/link-local ranges from Phase 4,
  now also blocks cloud metadata (`169.254.169.254`), carrier-grade NAT, IETF
  documentation/benchmark ranges, multicast/reserved space, and IPv4-mapped IPv6 bypass
  attempts (`::ffff:127.0.0.1`-style) that would otherwise sail past IPv4-only checks.
- **Rate limiting**: the internal callback API (120 req/min/IP, on top of HMAC
  verification) and CSV export (10 req/min/user, since it runs an unpaginated query) both
  have named limiters registered in `AppServiceProvider`.
- **Security headers**: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`,
  `Permissions-Policy` on every response; `Strict-Transport-Security` only when the request
  actually arrived over HTTPS (so local HTTP-only dev doesn't get a broken HSTS header).
- **Locked-down CORS**: `config/cors.php` only covers the two endpoints that need it
  (`/api/campaigns`, Sanctum's CSRF cookie route) and never allows a wildcard origin —
  enforced by a regression test, not just a comment.
- **`make audit`**: runs `composer audit` and `pip-audit` against both dependency trees.
- Commented-out production TLS server blocks in `infrastructure/nginx/default.conf`, ready
  to uncomment once real certificates exist.

## Prerequisites

- Docker and Docker Compose v2
- Ports 8080 (app), 8000 (python API), 3306 (MySQL), 6379 (Redis) free on your host, or
  override them in `.env`
- To use real SerpApi instead of mock data: a SerpApi account and API key

## Getting started

```bash
make install   # env files, composer install, artisan key:generate, builds frontend assets (Vite/Tailwind)
make up        # starts all 8 services
make migrate   # runs all migrations, including Phase 3's campaigns/leads/audits tables
make seed       # creates admin@example.com / sales@example.com / viewer@example.com (password: "password")
                # plus 3 demo campaigns with 10 leads + audits each, owned by the sales user
```

`make install` builds the Tailwind/Vite frontend assets using a throwaway `node:20-alpine`
container, so you don't need Node.js installed on your host. If you ever see
`ViteManifestNotFoundException`, it means that build step hasn't run yet (or `apps/web/public/build`
got wiped) — rebuild it directly with:

```bash
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html/apps/web node:20-alpine sh -c "npm install && npm run build"
```

On Windows PowerShell, replace `$(pwd)` with `${PWD}`.

**Important**: `INTERNAL_SHARED_SECRET` must be the exact same value in both
`apps/web/.env` and `apps/research-agent/.env` — it's how the two services' HMAC
signatures match. `.env.example` ships the same placeholder in both; change it in both
places together if you rotate it. **Before deploying anywhere near the real internet,
read `PRODUCTION-CHECKLIST.md` first** — the placeholder values in `.env.example` are not
safe to use as-is.

**To use real SerpApi** (instead of the default mock data): set `SERPAPI_API_KEY` and
`RESEARCH_PROVIDER=serpapi` in `apps/research-agent/.env`, then restart the Python
containers (`docker compose up -d --force-recreate python-api celery-worker`).

Then visit:
- `http://localhost:8080/login` — log in as any seeded user (password: `password`)
- `http://localhost:8080/` — dashboard (requires login)
- `http://localhost:8080/campaigns` — campaign list, scoped by account_type; open a
  campaign, click **Start**, then **View Leads** to browse/filter/export results
- `http://localhost:8080/api/health` — checks DB + Redis from inside Laravel
- `http://localhost:8000/internal/v1/health` — the Python service's health probe

## Running tests

```bash
docker compose exec laravel php artisan test
docker compose exec python-api pytest
make audit   # dependency vulnerability check, both stacks
```

Laravel covers everything from Phases 3–6, plus (Phase 7) security headers on every
response, HSTS only over HTTPS, CORS never allowing a wildcard origin, and both rate
limiters being registered with their expected limits.

Python covers (101 tests, all passing as delivered): everything from Phase 5, plus that
SerpApi errors never leak the API key (into logs, into `api_usage_logs`, or into a raised
exception), and the expanded SSRF blocklist including the IPv4-mapped IPv6 bypass case.

## A known limitation of this delivery environment

`apps/web/composer.lock` does not exist yet — this sandbox cannot reach Packagist to run
`composer install` and generate one. `make install` runs `composer install` inside the
`laravel` container the first time you run it on your machine (which does have normal
internet access), and that will generate the lock file. The Python side has no such
restriction: `apps/research-agent`'s dependencies were installed and the FastAPI app was
started with `uvicorn` and hit with `curl` before this was delivered to you.

## Commands

| Command | Purpose |
|---|---|
| `make install` | First-time setup: env files, image build, composer install, app key |
| `make up` / `make down` | Start / stop all containers |
| `make migrate` | Run Laravel migrations |
| `make seed` | Run Laravel seeders |
| `make test` | Run Laravel (`php artisan test`) and Python (`pytest`) suites |
| `make logs` | Tail all service logs |
| `make worker-logs` | Tail just `celery-worker` and `laravel-queue` |

## What's next

All 7 phases from the original plan are done. From here it's product-driven: real usage
will surface what's actually missing (self-service user provisioning? viewer-campaign
grants? a richer dashboard?) better than speculating now. `PRODUCTION-CHECKLIST.md` is the
gate before pointing this at real traffic.
