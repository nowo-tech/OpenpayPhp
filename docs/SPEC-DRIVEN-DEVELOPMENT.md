# Spec-driven development

This repository follows Nowo **REQ-*** bundle standards (see the local
`BUNDLES_FULL_SPECS_DETAILS.md` in `developer.local.server/repositories/bundles`)
plus a GitHub Spec Kit baseline under `specs/001-baseline/`.

## Product behaviour

- Clean-room Composer package `nowo-tech/openpay-php` (`Nowo\Openpay\` namespaces, MIT).
- Request-safe merchant credentials on php-fpm and FrankenPHP workers (`Session` / `Client`).
- Injectable HTTP client with explicit timeouts and OS-store TLS.
- PHP 8.3+, PHPUnit (Clover Lines ≥ 99%), PHP-CS-Fixer, scoped Rector/PHPStan.
- Current release **1.0.2** (tag `v1.0.2`); GitHub `nowo-tech/OpenpayPhp`.
- The former 3.x drop-in fork (`Openpay\\`, `replace` `openpay/sdk` 3.1.1) is documented in [CHANGELOG.md](CHANGELOG.md) and is **not** this tree.

## User stories

- **As** a merchant app **I want** `composer require nowo-tech/openpay-php:^1.0` **so that**
  I call the public Openpay REST API without process-wide static credentials.
- **As** a FrankenPHP worker **I want** `Session::run()` **so that**
  the next tenant does not inherit the previous API key.
- **As** a contributor **I want** `make release-check` **so that** style, analysis,
  and tests run the same way as other Nowo packages.

## REQ-* traceability

| Area | Where |
| ---- | ----- |
| Docker | `Dockerfile`, `docker-compose.yml` (`name: openpay-php`) |
| Makefile | `ensure-up`, `release-check`, `setup-hooks`, `update-deps` |
| QA | `composer cs-check` / `phpstan` / `test` / `test-coverage` |
| Docs | `docs/*` linked from README (REQ-DOCS-002) |
| CI | `.github/workflows/ci.yml` |
| Git | `.githooks/commit-msg`, `.scripts/check-no-cursor-coauthor.sh` |

## Layers

1. **Constitution** — `.specify/memory/constitution.md` (v1.1.0)
2. **Baseline spec** — `specs/001-baseline/` (shipped 1.0.2)
3. **Implementation** — `src/`, `tests/`

Validation: `make release-check` (PHPUnit, PHPStan, PHP-CS-Fixer, Rector dry-run, coverage gate).
