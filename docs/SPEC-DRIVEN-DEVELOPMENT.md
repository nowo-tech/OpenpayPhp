# Spec-driven development

In this repository, **spec-driven development** has three layers that stay in sync:

1. **GitHub Spec Kit baseline** — [`specs/001-baseline/`](../specs/001-baseline/) ([`spec.md`](../specs/001-baseline/spec.md), [`code-inventory.md`](../specs/001-baseline/code-inventory.md)), initialized with [GitHub Spec Kit](https://github.com/github/spec-kit) (`.specify/`, **Cursor Agent** skills in `.cursor/skills/speckit-*`). The inventory maps **100%** of the files under `src/`. **How to install, initialize, and use Spec Kit:** [`SPEC-KIT.md`](SPEC-KIT.md).
2. **Product behavior** — what `nowo-tech/openpay-php` guarantees to applications (see [`USAGE.md`](USAGE.md), [`CONFIGURATION.md`](CONFIGURATION.md), [`INSTALLATION.md`](INSTALLATION.md)). **PHPUnit** and **PHPStan** (FrankenPHP classic + worker rulesets) enforce the contracts in CI.
3. **Traceability anchors** — stable **`REQ-*`** identifiers in the Makefile, demo and hooks so tooling changes stay discoverable from issues and PRs.

There is no separate executable spec language (for example Gherkin); Spec Kit specs, tests, and static analysis are the mechanical proof alongside this document.

## Table of contents

- [User stories](#user-stories)
- [Package functional scope](#package-functional-scope)
  - [Goal](#goal)
  - [In scope](#in-scope)
  - [Explicit non-goals](#explicit-non-goals)
- [Validating the functional spec](#validating-the-functional-spec)
- [Requirement identifiers (`REQ-*`)](#requirement-identifiers-req-)
- [Suggested workflow for contributors](#suggested-workflow-for-contributors)
- [Relationship to Engram](#relationship-to-engram)
- [GitHub Spec Kit (summary)](#github-spec-kit-summary)
- [See also](#see-also)

## User stories

| ID | Story | Integrator docs |
| --- | --- | --- |
| US-01 | As a merchant app, I `composer require nowo-tech/openpay-php:^1.1` and call the public Openpay REST API (charges, customers, cards, tokens, webhooks) without process-wide static credentials. | [USAGE.md](USAGE.md), [INSTALLATION.md](INSTALLATION.md) |
| US-02 | As an app on FrankenPHP worker mode (kernel not reset), I use `Session::run()` with request-scoped `Credentials` so the next tenant never inherits the previous API key or client IP. | [USAGE — FrankenPHP worker mode](USAGE.md#frankenphp-worker-mode), [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md) |
| US-03 | As an integrator, I handle Openpay API errors through typed exceptions (HTTP status, error code, category, request id). | [USAGE — Errors](USAGE.md#errors) |
| US-04 | As an integrator, I inject my own `HttpClient` (or tune cURL timeouts) for tests and interactive payment flows. | [CONFIGURATION.md](CONFIGURATION.md) |
| US-05 | As a contributor, I run `make release-check` so style, analysis and tests run like in every other Nowo package. | [CONTRIBUTING.md](CONTRIBUTING.md) |

## Package functional scope

### Goal

A clean-room, MIT-licensed PHP client (`Nowo\Openpay\`) for the **public** Openpay REST API (Mexico, Colombia, Peru), safe under php-fpm and FrankenPHP workers.

### In scope

- `Credentials` (merchant id, private key, country, sandbox flag, client public IP) and `Country` host selection.
- `Session` / `Client` with merchant-root resources (`charges`, `customers`, `cards`, `tokens`, `webhooks`) and customer-scoped `charges` / `cards`.
- `ResourceApi` CRUD helpers (`create`/`add`, `get`, `getList`, `update`, `delete`, `refund`, `nested`).
- `HttpClient` abstraction with the default `CurlHttpClient` (explicit connect/request timeouts, OS-store TLS).
- Error mapping to `OpenpayException` / `OpenpayApiException`.

### Explicit non-goals

- Redistributing or wrapping the official `openpay/sdk` (the former 3.x drop-in fork is documented in [CHANGELOG.md](CHANGELOG.md) and is not this tree).
- Framework integration (no Symfony bundle, no DI configuration); the Symfony 8 demo is illustrative only.
- Card data handling beyond what the public REST API accepts (PCI scope stays with the merchant / Openpay.js tokens).
- Process-wide static or global merchant configuration.

## Validating the functional spec

- **`make qa`** (Docker) or **`composer qa`**: PHP-CS-Fixer, PHPStan (FrankenPHP classic + worker rulesets), PHPUnit.
- **`composer test-coverage`** / **`make release-check`**: coverage gate ≥ 99% lines (currently 100%, see [COVERAGE.md](COVERAGE.md)).
- Behavior changes require tests under `tests/` and an update of [`specs/001-baseline/spec.md`](../specs/001-baseline/spec.md).

## Requirement identifiers (`REQ-*`)

| ID | Location | Meaning |
| --- | --- | --- |
| REQ-MAKE-001 / REQ-MAKE-002 / REQ-MAKE-006 / REQ-MAKE-008 / REQ-MAKE-010 | Root `Makefile` header | Docker workflow, `release-check`, `setup-hooks`, `update-deps`, Compose detection |
| REQ-CS-008 | Root `Makefile` (`igor`) | Igor worker-state audit |
| REQ-GIT-001 | `.githooks/commit-msg`, `.scripts/check-no-cursor-coauthor.sh`, CI `git-hygiene` | No AI co-author trailers in history |
| REQ-RUNTIME-001 | `src/Http/CurlHttpClient.php` | Explicit connect/request timeouts |
| REQ-DEMO-001 / REQ-DEMO-010 | `demo/symfony8` | Twig Inspector + Hot Reload; `FRANKENPHP_MODE` classic/worker switch |

When scripted behavior changes, update the comment next to the existing `REQ-*` or add a new ID and cite it from PRs/issues.

## Suggested workflow for contributors

1. **Clarify** the behavior in an issue, the baseline spec, or a new `specs/00N-*` folder.
2. **Implement with tests** (`composer test`, `composer phpstan`).
3. **Anchor scripts and demos** with `REQ-*` comments when dev UX changes.
4. **Update Spec Kit artifacts** — `spec.md` and `code-inventory.md` when `src/` changes (see the maintainer checklist in [SPEC-KIT.md](SPEC-KIT.md)).
5. **Ship integrator docs** (`USAGE.md`, `CONFIGURATION.md`, `CHANGELOG.md`, `UPGRADING.md`); validate with `make release-check`.

## Relationship to Engram

[ENGRAM.md](ENGRAM.md) documents the org-wide checklist plane (Engram MCP memory, documentation hygiene). It does **not** replace this document: the functional contract (user stories, scope, validation, `REQ-*` anchors) lives here and in `specs/001-baseline/`.

## GitHub Spec Kit (summary)

| Artifact | Path |
| --- | --- |
| Baseline spec | [`specs/001-baseline/spec.md`](../specs/001-baseline/spec.md) |
| Code inventory (100% of `src/`) | [`specs/001-baseline/code-inventory.md`](../specs/001-baseline/code-inventory.md) |
| Constitution | [`.specify/memory/constitution.md`](../.specify/memory/constitution.md) |
| Cursor skills | `.cursor/skills/speckit-*` |

Re-initialize with `specify init --here --force --integration cursor-agent --script sh`. **Full manual:** [SPEC-KIT.md](SPEC-KIT.md).

## See also

- [SPEC-KIT.md](SPEC-KIT.md) · [`specs/001-baseline/spec.md`](../specs/001-baseline/spec.md)
- [USAGE.md](USAGE.md) · [CONFIGURATION.md](CONFIGURATION.md) · [CONTRIBUTING.md](CONTRIBUTING.md) · [RELEASE.md](RELEASE.md)
- [DEMO-FRANKENPHP.md](DEMO-FRANKENPHP.md) · [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md)
