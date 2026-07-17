.DEFAULT_GOAL := help
COMPOSE := docker compose

.PHONY: install up down migrate seed test logs worker-logs audit help

install: ## Copy env files, install PHP/Python deps, and build frontend assets
	[ -f .env ] || cp .env.example .env
	[ -f apps/web/.env ] || cp apps/web/.env.example apps/web/.env
	[ -f apps/research-agent/.env ] || cp apps/research-agent/.env.example apps/research-agent/.env
	$(COMPOSE) build
	$(COMPOSE) run --rm laravel composer install
	$(COMPOSE) run --rm laravel php artisan key:generate
	docker run --rm -v "$(CURDIR):/var/www/html" -w /var/www/html/apps/web node:20-alpine sh -c "npm install && npm run build"

up: ## Start all services in the background
	$(COMPOSE) up -d

down: ## Stop and remove all containers
	$(COMPOSE) down

migrate: ## Run Laravel migrations
	$(COMPOSE) exec laravel php artisan migrate

seed: ## Run Laravel database seeders
	$(COMPOSE) exec laravel php artisan db:seed

test: ## Run Laravel and Python test suites
	$(COMPOSE) exec laravel php artisan test
	$(COMPOSE) exec python-api pytest

logs: ## Tail logs for every service
	$(COMPOSE) logs -f

worker-logs: ## Tail logs for the Celery worker and Laravel queue worker only
	$(COMPOSE) logs -f celery-worker laravel-queue

audit: ## Check both dependency trees for known security advisories
	$(COMPOSE) exec laravel composer audit
	$(COMPOSE) exec python-api pip install --quiet pip-audit && $(COMPOSE) exec python-api pip-audit

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-16s\033[0m %s\n", $$1, $$2}'
