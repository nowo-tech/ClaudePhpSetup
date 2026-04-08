# Claude PHP Setup — development targets use the root docker-compose.yml.

SHELL := /bin/bash

COMPOSE_FILE := docker-compose.yml
COMPOSE     := docker compose -f $(COMPOSE_FILE)
SERVICE_PHP := php

.PHONY: help up down build shell install ensure-up test test-coverage cs-check cs-fix rector rector-dry phpstan qa \
	release-check release-check-demos composer-sync clean update validate assets setup-hooks

help:
	@echo "Claude PHP Setup — Development Commands"
	@echo ""
	@echo "  up              Start Docker container"
	@echo "  down            Stop Docker container"
	@echo "  build           Rebuild Docker image (no cache)"
	@echo "  shell           Open shell in container"
	@echo "  install         Install Composer dependencies"
	@echo "  assets          No-op (no frontend in this package)"
	@echo "  test            Run PHPUnit tests"
	@echo "  test-coverage   Run tests with code coverage + global Lines %%"
	@echo "  cs-check / cs-fix  Code style"
	@echo "  rector / rector-dry  Rector"
	@echo "  phpstan         Static analysis"
	@echo "  qa              cs-check + phpstan + test"
	@echo "  release-check   Pre-release pipeline"
	@echo "  composer-sync   Validate composer.json and refresh lock metadata"
	@echo "  clean           Remove vendor and local artifacts"
	@echo "  update / validate  Composer"
	@echo "  setup           Run claude-php-setup wizard (dogfooding)"
	@echo "  setup-hooks     Install git hooks from .githooks/"
	@echo ""

build:
	$(COMPOSE) build --no-cache

up:
	$(COMPOSE) build
	$(COMPOSE) up -d
	@echo "Installing dependencies..."
	$(COMPOSE) exec -T $(SERVICE_PHP) composer install --no-interaction
	@echo "Container ready."

down:
	$(COMPOSE) down

shell:
	$(COMPOSE) exec $(SERVICE_PHP) sh

install: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer install

ensure-up:
	@if ! $(COMPOSE) exec -T $(SERVICE_PHP) true 2>/dev/null; then \
		echo "Starting container (root docker-compose)..."; \
		$(COMPOSE) up -d; \
		sleep 3; \
		$(COMPOSE) exec -T $(SERVICE_PHP) composer install --no-interaction; \
	fi

test: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer test

test-coverage: ensure-up
	@mkdir -p .coverage
	@set -o pipefail; $(COMPOSE) exec -T $(SERVICE_PHP) composer test-coverage 2>&1 | tee .coverage/coverage-php.txt
	sh .scripts/php-coverage-percent.sh .coverage/coverage-php.txt

cs-check: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer cs-check

cs-fix: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer cs-fix

rector: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer rector

rector-dry: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer rector-dry

phpstan: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer phpstan

qa: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer qa

update: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer update --no-interaction

validate: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer validate --strict

composer-sync: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer validate --strict
	$(COMPOSE) exec -T $(SERVICE_PHP) composer update --no-install

release-check: ensure-up composer-sync cs-fix cs-check rector-dry phpstan test-coverage release-check-demos

release-check-demos:
	@if [ -f demo/Makefile ]; then $(MAKE) -C demo release-check; else echo "No demo/Makefile — skip release-check-demos"; fi

setup: ensure-up
	$(COMPOSE) exec -T $(SERVICE_PHP) composer setup

setup-hooks:
	git config core.hooksPath .githooks
	@echo "Git hooks installed."

clean:
	rm -rf vendor .phpunit.cache coverage coverage.xml .php-cs-fixer.cache .coverage
	rm -f coverage-php.txt

assets:
	@echo "No frontend assets in this package."
