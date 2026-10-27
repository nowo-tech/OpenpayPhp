# Makefile for Openpay PHP SDK (Nowo fork)
# REQ-MAKE-001 / REQ-MAKE-002 / REQ-MAKE-006 / REQ-MAKE-008 / REQ-MAKE-010

.PHONY: help ensure-up up down down-dev build shell install assets test test-coverage cs-check cs-fix rector rector-dry phpstan igor qa release-check composer-sync clean update validate setup-hooks check-no-cursor-coauthor strip-cursor-coauthor-from-history check-open-prs check-no-raw-html-form check-version bump-version demo-smoke release-check-demos update-deps

COMPOSER_BIN := /usr/bin/composer
COMPOSE := $(shell docker compose version >/dev/null 2>&1 && echo "docker compose" || echo "docker-compose")

help:
	@echo "Openpay PHP SDK - Development Commands"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "Targets:"
	@echo "  up            Start Docker container"
	@echo "  down          Stop Docker container"
	@echo "  down-dev      Stop root compose and remove orphans"
	@echo "  build         Build Docker image"
	@echo "  shell         Open shell in container"
	@echo "  install       Install Composer dependencies"
	@echo "  assets        No-op (no frontend assets in this library)"
	@echo "  test          Run PHPUnit tests"
	@echo "  test-coverage Run tests with code coverage"
	@echo "  cs-check      Check code style"
	@echo "  cs-fix        Fix code style"
	@echo "  rector        Apply Rector refactoring"
	@echo "  rector-dry    Run Rector in dry-run mode"
	@echo "  phpstan       Run PHPStan static analysis"
	@echo "  igor          Run Igor worker-state audit (REQ-CS-008)"
	@echo "  qa            Run QA checks (cs-check + phpstan + test)"
	@echo "  release-check Run the full pre-release validation chain"
	@echo "  composer-sync Validate composer.json and align composer.lock (no install)"
	@echo "  clean         Remove vendor, caches and coverage output"
	@echo "  update        Update composer.lock (composer update)"
	@echo "  update-deps   Update library and demo dependencies (REQ-MAKE-008)"
	@echo "  validate      Run composer validate --strict"
	@echo ""
	@echo "Library-specific:"
	@echo "  check-open-prs Fail if unresolved open GitHub PRs remain (REQ-REL-003)"
	@echo "  check-version Fail if src/Version.php, composer extra.nowo-package-version and the release tag disagree"
	@echo "  bump-version  Set Version::VERSION + extra.nowo-package-version (make bump-version VERSION=X.Y.Z)"
	@echo "  check-no-raw-html-form Fail on raw <form>/<input>/<textarea>/<select> in demo Twig (REQ-TWIG-005)"
	@echo "  demo-smoke    Boot demo/symfony8 and assert a final HTTP 200 (REQ-TEST-011)"
	@echo "  setup-hooks   Install git hooks (REQ-GIT-001)"
	@echo ""
	@echo "Demos: make -C demo (see demo/symfony8/README.md)"
	@echo ""

ensure-up:
	@if ! $(COMPOSE) exec -T php true 2>/dev/null; then \
		echo "Starting container..."; \
		$(COMPOSE) up -d --build; \
		sleep 3; \
		$(COMPOSE) exec -T php $(COMPOSER_BIN) install --no-interaction; \
	fi

build:
	$(COMPOSE) build

up:
	@echo "Building Docker image..."
	$(COMPOSE) build
	@echo "Starting container..."
	$(COMPOSE) up -d
	@echo "Waiting for container to be ready..."
	@sleep 2
	@echo "Installing dependencies..."
	$(COMPOSE) exec -T php $(COMPOSER_BIN) install --no-interaction
	@echo "Container ready"

down:
	$(COMPOSE) down

down-dev:
	$(COMPOSE) down --remove-orphans

shell: ensure-up
	$(COMPOSE) exec php sh

install: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) install --no-interaction

assets:
	@echo "No frontend assets in this library."

test: ensure-up
	$(COMPOSE) exec php $(COMPOSER_BIN) test

test-coverage: ensure-up
	$(COMPOSE) exec php $(COMPOSER_BIN) test-coverage | tee coverage-php.txt
	sh ./.scripts/php-coverage-percent.sh coverage-php.txt

cs-check: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) cs-check

cs-fix: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) cs-fix

rector: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) rector

rector-dry: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) rector-dry

phpstan: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) phpstan

qa: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) qa

composer-sync: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) validate --strict
	$(COMPOSE) exec -T php $(COMPOSER_BIN) update --lock --no-interaction --no-install


# Run Igor worker-state audit (REQ-CS-008)
igor: ensure-up
	$(COMPOSE) exec -T php composer igor
release-check:
	@$(MAKE) ensure-up
	@$(MAKE) check-no-cursor-coauthor
	@$(MAKE) check-open-prs
	@$(MAKE) check-no-raw-html-form
	@$(MAKE) check-version
	@$(MAKE) composer-sync
	@$(MAKE) cs-fix
	@$(MAKE) cs-check
	@$(MAKE) rector-dry
	@$(MAKE) phpstan
	@$(MAKE) igor
	@$(MAKE) test-coverage
	@$(MAKE) release-check-demos

release-check-demos:
	@if [ -f demo/Makefile ]; then $(MAKE) -C demo release-check; fi

# REQ-REL-003 — no unresolved open GitHub PRs before release
check-open-prs:
	@chmod +x .scripts/check-open-prs.sh
	@bash .scripts/check-open-prs.sh

# REQ-TWIG-005 — demo Twig must use Symfony Form Types (form_start / form_row / form_end), never raw HTML controls
check-no-raw-html-form:
	@if grep -rEn --include='*.twig' '<(form|input|textarea|select)[[:space:]>]' demo 2>/dev/null | grep -v '/vendor/'; then \
		echo "REQ-TWIG-005: raw HTML form controls found in Twig (use a Form Type + form_start/form_row/form_end)."; exit 1; \
	fi
	@echo "REQ-TWIG-005: no raw <form>/<input>/<textarea>/<select> in Twig."

# Release version gate (src/Version.php = composer extra = tag); bump both in the release commit
check-version:
	@bash .scripts/check-version.sh

bump-version:
	@echo "$(VERSION)" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+$$' || { echo "Usage: make bump-version VERSION=X.Y.Z"; exit 1; }
	@sed -i.bak -E "s/(const string VERSION = ')[^']*'/\1$(VERSION)'/" src/Version.php && rm -f src/Version.php.bak
	@sed -i.bak -E 's/("nowo-package-version": ")[^"]*"/\1$(VERSION)"/' composer.json && rm -f composer.json.bak
	@bash .scripts/check-version.sh v$(VERSION)

# REQ-TEST-011 — boot the demo and assert a final HTTP 200
demo-smoke:
	@$(MAKE) -C demo demo-smoke

update: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) update --no-interaction

validate: ensure-up
	$(COMPOSE) exec -T php $(COMPOSER_BIN) validate --strict

clean:
	rm -rf vendor
	rm -rf .phpunit.cache
	rm -rf coverage
	rm -f coverage.xml
	rm -f coverage-php.txt
	rm -f .php-cs-fixer.cache

check-no-cursor-coauthor:
	@chmod +x .scripts/check-no-cursor-coauthor.sh
	@./.scripts/check-no-cursor-coauthor.sh HEAD

setup-hooks:
	@chmod +x .githooks/pre-commit 2>/dev/null || true
	@chmod +x .githooks/commit-msg 2>/dev/null || true
	@git config core.hooksPath .githooks
	@echo "Git hooks installed (.githooks — includes commit-msg for REQ-GIT-001)."

# REQ-MAKE-008: update-deps (REQ-MAKE-008)
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST))))
-include $(BUNDLE_ROOT)/../.scripts/Makefile.update-deps.mk

strip-cursor-coauthor-from-history:
	@chmod +x .scripts/strip-cursor-coauthor-from-history.sh
	@./.scripts/strip-cursor-coauthor-from-history.sh main
