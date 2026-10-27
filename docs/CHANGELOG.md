# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

The Composer package is [`nowo-tech/openpay-php`](https://packagist.org/packages/nowo-tech/openpay-php).

**Version lines on this package name:**

| Line | API | Packagist today |
| --- | --- | --- |
| **1.x** (`main`) | MIT clean-room `Nowo\Openpay\` | Installable (`v1.0.0`–`v1.1.1`) |
| **3.x** (former `master` fork) | Apache-2.0 drop-in `Openpay\` (`replace` `openpay/sdk` 3.1.1) | Tags were dropped from this VCS; Composer **cannot** resolve `3.1.1.1` / `3.2.0` / `v3.2.1` |

Consumers that still call `Openpay\Data\Openpay` / `OpenpayApi::createRoot()` need either the **3.x fork** or a migration to `Client`/`Session` (1.1+ includes **webhooks**). Until 3.x tags are restored on a Packagist-connected ref, use the archived tree [`nowo-tech/OpenpayPhp-fork-archive`](https://github.com/nowo-tech/OpenpayPhp-fork-archive) branch `master` (commit `bb01997`, release **3.2.1**).

## Table of contents

- [[Unreleased]](#unreleased)
- [[1.2.0] - 2026-10-22](#120---2026-10-22)
- [[1.1.4] - 2026-10-13](#114---2026-10-13)
- [[1.1.3] - 2026-10-09](#113---2026-10-09)
- [[1.1.2] - 2026-09-27](#112---2026-09-27)
- [[1.1.1] - 2026-09-24](#111---2026-09-24)
- [[1.1.0] - 2026-09-22](#110---2026-09-22)
- [[1.0.2] - 2026-09-07](#102---2026-09-07)
- [[1.0.1] - 2026-08-24](#101---2026-08-24)
- [[1.0.0] - 2026-08-21](#100---2026-08-21)
- [[3.2.1] - 2026-08-21](#321---2026-08-21)
- [[3.2.0] - 2026-08-21](#320---2026-08-21)
- [[3.1.1.1] - 2026-08-21](#3111---2026-08-21)

## [Unreleased]

### Added

- `Nowo\Openpay\Http\Psr18HttpClient`: PSR-18 transport adapter (any `Psr\Http\Client\ClientInterface` + PSR-17 request/stream factories, e.g. Symfony `Psr18Client` or Guzzle) for `Client` / `Session`; a PSR-18 `ClientExceptionInterface` becomes `OpenpayException` (REQ-CS-007). `psr/http-client`, `psr/http-factory` and `psr/http-message` are new runtime requirements (interfaces only). See [UPGRADING](UPGRADING.md#unreleased).

### Changed

- Demo: the "Create customer" form is now a Symfony Form Type (`App\Form\CustomerType`, CSRF + validation) rendered with `form_start` / child `form_row` loop / `form_end`; new `make check-no-raw-html-form` gate (part of `release-check`) fails on raw `<form>` / `<input>` / `<textarea>` / `<select>` in demo Twig (REQ-DEMO-001, REQ-TWIG-005).

### Security

Hardening from the 2026-10-27 AI security re-audit (Pass (good) / Low; findings L1–L4, I1–I7 — see [SECURITY.md](SECURITY.md#ai-security-audit)):

- `#[\SensitiveParameter]` on every payload, JSON body and header parameter (`HttpClient` / `CurlHttpClient` / `Psr18HttpClient::request`, `Client::request`, `ResourceApi::create/add/update/refund`), so card data and the `Authorization` header never appear in stack-trace arguments (L1).
- Resource ids must match `^[A-Za-z0-9_-]{1,64}$` and child resource names `^[a-z_]+$`; `''`, `.`, `..`, separators and control characters throw `OpenpayException` before any request (L2).
- `OpenpayApiException` message is built from the sanitized structured fields (control characters stripped, 200-character cap) instead of the raw body; `responseBody` is capped at 64 KiB and documented as untrusted; API strings in log contexts are capped at 128 bytes (L3).
- `Credentials` validates the merchant id (`^[A-Za-z0-9]+$`) and `publicIp` (`FILTER_VALIDATE_IP`); `CurlHttpClient` rejects CR / LF / NUL in header names and values (L4).
- `CurlHttpClient` sets `CURLOPT_SSL_VERIFYPEER`, `CURLOPT_SSL_VERIFYHOST=2`, `CURLOPT_FOLLOWLOCATION=false` and `CURLOPT_PROTOCOLS=CURLPROTO_HTTPS` explicitly (I1).
- Demo: unknown `OPENPAY_COUNTRY` is a configuration error (no silent MX fallback), transport errors render a generic 502 page, the port is bound to `127.0.0.1` (I5, I6).
- `Version::VERSION` / `extra.nowo-package-version` corrected to 1.2.0; new `make bump-version VERSION=X.Y.Z` and `make check-version` (in `release-check`), and `release.yml` fails when the tag does not match (I7).

### Documentation

- `docs/SECURITY.md`: PSR-18 redirect / exception-chain notes, trusted-proxy client IP, `zend.exception_ignore_args`, payments and webhooks (`hash_equals`, https-only URL, `order_id`, no blind retry), new "AI security audit" section; PSR-18 examples use `max_redirects: 0` (I2–I4).
- `docs/PSR.md`: PSR-18 / PSR-17 adopted through `Psr18HttpClient`; summary updated (PSR-3 + PSR-18/PSR-17 adopted). CONFIGURATION, SECURITY, worker audit, USAGE and Spec Kit inventory list the PSR-18 transport.

## [1.2.0] - 2026-10-22

Optional PSR-3 logging of API calls, cURL timeout test, full security guide, release gates and demo smoke workflow. See [UPGRADING](UPGRADING.md#to-120).

### Added

- Optional PSR-3 logger on `Client` / `Session` (third constructor argument): API calls (`info`), API errors with Openpay error code / category / request id (`warning`) and transport failures (`error`), never keys, payloads or card data (REQ-OBS-001). `psr/log` is now a runtime requirement.

### Changed

- Dependencies refreshed (Composer and pnpm/npm lock files of the bundle and its demos, campaign 2026-10-19).
- A cURL timeout / transport failure is now covered by a test against an unresponsive local endpoint (REQ-RUNTIME-001); the demo sets PHP `max_execution_time` 45 s and FrankenPHP `max_wait_time` 30 s, documented with the PHP image policy in `docs/DEMO-FRANKENPHP.md` and in `docs/CONFIGURATION.md` (Timeouts, Logging) (REQ-DEMO-010).
- `docs/SECURITY.md` rewritten with scope, attack surface, threat model, secrets, timeouts, logging, dependencies, exposure and the release security checklist; `.github/SECURITY.md` follows the canonical template (1.x, `hectorfranco@nowo.tech`) (REQ-SEC-001, REQ-SEC-002, REQ-SEC-003).
- `release-check` starts with `ensure-up` and runs `check-open-prs`, `igor` and the demo release check; root `make demo-smoke` and a `Demo smoke` workflow (final HTTP 200) (REQ-MAKE-002, REQ-CS-008, REQ-TEST-011); `make help` follows the standard block order (REQ-MAKE-001).
- PHPDoc on every public class and method (REQ-CS-001); Cursor rules describe the repository as a library (REQ-IDE-004); demo DNS override commented (REQ-DEMO-009); the co-author check accepts git worktrees.

### Documentation

- Documentation: wave-1 REQ compliance — README rebuilt in the canonical order (badges incl. Downloads/GitHub stars/Coverage, FrankenPHP claim, Features → Installation → Quick start → Requirements → Documentation → Tests and coverage), canonical issue templates (legacy bug_report/feature_request removed), CODEOWNERS and PR template, CONTRIBUTING Cursor Agent settings, TOCs, DEMO-FRANKENPHP dev/prod start, timeouts and troubleshooting, commented demo `.env.example`, demo Compose name `openpay-php-demo-symfony-8`, spec-driven development rewrite, full GitHub Spec Kit manual, baseline spec (US-*, grouped FR-*, SC-*) and categorized inventory (11/11).

[1.2.0]: https://github.com/nowo-tech/OpenpayPhp/releases/tag/v1.2.0

## [1.1.4] - 2026-10-13

### Fixed

- **Identity:** `Version::VERSION` (sent in the `User-Agent`) was stuck at `1.1.1` while `v1.1.3` was tagged; it and `extra.nowo-package-version` now report `1.1.4`, and `tests/Unit/VersionTest.php` keeps both in sync.
- **Refund guard:** `ResourceApi::refund()` only accepts `charges` or `…/{id}/charges` resources; the old `str_contains(path, 'charges')` check let any nested resource whose id contained "charges" through.

### Added

- **API errors:** HTTP >= 400 responses throw `Nowo\Openpay\Exception\OpenpayApiException` (extends `OpenpayException`) exposing `httpStatus`, Openpay `errorCode`, `category`, `description`, `requestId` and `responseBody`. Message and `getCode()` (HTTP status) are unchanged.

### Security

- `Credentials` marks `$privateKey` with `#[\SensitiveParameter]` so it is redacted from stack traces.

### Changed

- Composer: PHP constraint `>=8.3 <8.6` (upper bound like the sibling packages; CI covers 8.3–8.5).

[1.1.4]: https://github.com/nowo-tech/OpenpayPhp/releases/tag/v1.1.4

## [1.1.3] - 2026-10-09

### Fixed

- Demo (Symfony 8): removed `config/packages/twig_component.yaml` and `validator.yaml`, which configured bundles the demo does not install and broke `cache:clear`.
- Makefile: `strip-cursor-coauthor-from-history` targets the `main` branch (was `master`, a no-op).

### Changed

- Dev dependencies (Dependabot + lock refresh): PHPStan 2.3.1, PHPUnit 11.5.57, PHP-CS-Fixer 3.95.27, Rector 2.7.0, `igor-php/igor-php` 0.10.1, `nowo-tech/phpstan-frankenphp` 1.2.3.
- Demo (Symfony 8): Twig 3.30.0, `twig/extra-bundle` 3.29.0, HotReloadBundle 1.5.5, TwigInspectorBundle 1.1.7; regenerated `config/reference.php`.

[1.1.3]: https://github.com/nowo-tech/OpenpayPhp/releases/tag/v1.1.3

## [1.1.2] - 2026-09-27

### Added

- **REQ-CS-008:** `igor-php/igor-php` (require-dev only), root `igor.json`, Composer/`Makefile` `igor` target, and `release-check` wiring for FrankenPHP worker-state audit.

[1.1.2]: https://github.com/nowo-tech/OpenpayPhp/releases/tag/v1.1.2

## [1.1.1] - 2026-09-24

### Added

- **Docs:** `docs/FRANKENPHP-WORKER-AUDIT.md` — full audit for FrankenPHP worker with kernel **not** reset (scenario B); verdict **Viable**.

### Changed

- **PHPStan:** include `ruleset-worker-strict.neon` (covers worker rules; no request-superglobal usage in `src/`).
- **Immutability:** Rector promotes core types to `final readonly class` (`Client`, `Credentials`, `Session`, `ResourceApi`, `CurlHttpClient`, `HttpResponse`).
- **Demo:** `PaymentController` builds request-scoped `Credentials` with the client IP (worker-safe pattern).
- **Identity:** `Version::VERSION` and `extra.nowo-package-version` report `1.1.1`.

### Documentation

- **USAGE.md** / **CONFIGURATION.md** / **DEMO-FRANKENPHP.md** / **README.md** / **SECURITY.md**: worker guidance and link to the audit.
- **specs/001-baseline:** status `1.1.1`; FR-004 + US-002 for worker scenario B.

### Notes

- **No public API changes.** Integrators on `^1.1` only need `composer update`.
- Residual **Low** finding: default cURL timeouts (5 s / 30 s) can occupy a worker thread; tune via `CurlHttpClient` if needed.

[1.1.1]: https://github.com/nowo-tech/OpenpayPhp/releases/tag/v1.1.1

## [1.1.0] - 2026-09-22

### Added

- **`Client::$webhooks`** — merchant webhook CRUD via `ResourceApi` (`create`/`add`, `get`, `getList`, `delete`, `update`).
- **`ResourceApi::add()`** — alias of `create()` for legacy Openpay SDK naming.

### Notes

- Responses remain decoded **arrays** (not objects). Apps migrating from 3.x must map `$res->id` → `$res['id']`, etc.
- core-nowo / similar: after adapting the payment factory, prefer `^1.1` over the fork archive.

[1.1.0]: https://github.com/nowo-tech/OpenpayPhp/releases/tag/v1.1.0

## [1.0.2] - 2026-09-07

### Changed

- **Docs:** restore 3.1.1.1 / 3.2.x fork history in CHANGELOG, UPGRADING, and RELEASE; align SECURITY / CONTRIBUTING / Spec Kit with the 1.0.x clean-room line.
- **Identity:** `Version::VERSION` and `extra.nowo-package-version` report `1.0.2` (v1.0.1 still shipped `1.0.0` in the constant).

### Notes

- **No API changes.** Integrators on `^1.0` only need `composer update`.
- Apps that still use `Openpay\` must stay on the 3.x fork (archive VCS), not this release.

[1.0.2]: https://github.com/nowo-tech/OpenpayPhp/releases/tag/v1.0.2

## [1.0.1] - 2026-08-24

### Changed

- **Tests:** expand coverage toward 100% (REQ-TEST-003).
- **Dependencies:** routine Composer/npm bumps (Dependabot).
- **Docs:** Spec Kit baseline refresh.

### Notes

- **No API or configuration changes** for integrators unless noted above.

[1.0.1]: https://github.com/nowo-tech/OpenpayPhp/releases/tag/v1.0.1

## [1.0.0] - 2026-08-21

Clean-room rewrite. **Not** a drop-in for `openpay/sdk` or for Nowo fork `3.1.1.1` / `3.2.x`.

### Added

- Clean-room MIT SDK `Nowo\Openpay\` (Client, Session, Credentials, Charges/Customers/Cards/Tokens)
- FrankenPHP worker-safe design (no static merchant credentials)
- Symfony 8 + FrankenPHP demo (`demo/symfony8`)
- PHPStan level 8 + FrankenPHP rulesets

### Removed

- Drop-in `Openpay\` fork of `openpay/sdk` and Composer `replace`
- Git tags `3.1.1.1`, `3.2.0`, and `v3.2.1` from this repository (history rewrite). Packagist still lists them in the UI but Composer cannot install them.

[1.0.0]: https://github.com/nowo-tech/OpenpayPhp/releases/tag/v1.0.0

## [3.2.1] - 2026-08-21

Last Apache-2.0 **fork** release (`Openpay\`, `replace` `openpay/sdk` 3.1.1). Code lives on local/archive `master` (`778e7d4`); **this tag is not on Packagist**.

Patch on **3.2.0**: Nowo REQ-* scaffold, coverage gate, and a PHP 8 cache-id fix.
Public Openpay resource API is unchanged.

### Added

- REQ-* library scaffold: Docker/Make QA, Cursor/Spec Kit docs, `docs/` map.
- CI `git-hygiene` job (`fetch-depth: 0`) for REQ-GIT-001.
- PHPUnit coverage ≥ 99% of `Openpay/` (REQ-TEST-003); Clover Lines **99.63%**.
- Spec Kit baseline (`specs/001-baseline/`) for SPECKIT-003.
- GitHub repository slug PascalCase `nowo-tech/OpenpayPhp` (REQ-DOCS-014). Packagist name stays `nowo-tech/openpay-php`.

### Fixed

- `OpenpayApiDerivedResource` cache ids are cast to string before `strtolower()`
  (PHP 8 TypeError on integer fallback ids).

### Changed

- User-Agent is `OpenpayPhp/3.2.1`.
- Rector (`make rector-dry`) scoped to Nowo HTTP/session files + `tests/` (same surface as PHPStan). `OpenpaySession` / `CurlHttpTransport` are `readonly`.

## [3.2.0] - 2026-08-21

Fork release on top of `3.1.1.1`. PHP 8.3+, request-isolated credentials, injectable HTTP.
**This tag is not on Packagist.**

### Added

- `Openpay::configureFromEnvironment()` to reload `OPENPAY_*` after `reset()`.
- `Openpay::setHttpTransport()` / `OpenpayHttpTransport` / `CurlHttpTransport`
  (cURL remains the default; no new production dependencies).
- `OpenpaySession` — configure, run a callback, always `reset()` (including on throw).
- `Openpay::VERSION` (`3.2.0`); User-Agent is `OpenpayPhp/3.2.0`.
- PHPUnit 11 suite and GitHub Actions on PHP 8.3–8.6.
- `ext-json` and `ext-mbstring` declared in `composer.json`.

### Changed

- PHP constraint is `>=8.3` (honest: `#[Override]` already required 8.3).
- After `reset()`, `getApiKey()` / `getId()` / `getSandboxMode()` no longer
  fall back to `OPENPAY_*` process environment.
- Root `Openpay.php` is a PSR-4 autoload for manual installs (no eager `require()`).
- JSON encode/decode uses `JSON_THROW_ON_ERROR`.
- `declare(strict_types=1)` on every PHP file; closing `?>` removed.
- `OpenpayApiResourceBase::__set` no longer treats `0` / `false` as empty.

### Removed

- Bundled `Openpay/Data/cacert.pem` (Mozilla CA data from August 2023). TLS uses the OS trust store.
- `curl_close()` and `utf8_encode()` (deprecated / removed on current PHP).
- Eclipse `.project` and `NOTES.txt` (SDK 1.2.0 / PHP 5.2).
- Fossil `composer.lock` pinning PHPUnit 4.8.

### Security

- Debug logs no longer dump HTTP bodies or `__set` property values.
- `reset()` no longer lets a later tenant inherit `OPENPAY_API_KEY` from the worker process.

### Fixed

- `refreshData()` guarded with `isset()` (PHP 8 undefined-key warnings).
- `OpenpayApiError` casts message/code before `Exception::__construct()`.
- Extra argument to `encodeToQueryString()` removed.

## [3.1.1.1] - 2026-08-21

First Packagist release of the Nowo fork (`nowo-tech/openpay-php`). Commit `f9153cab`.
**Composer can no longer resolve this version** (tag missing from the Packagist VCS).

- Drop-in `replace` of `openpay/sdk` 3.1.1.
- `Openpay::configure()`, `Openpay::reset()`, `OpenpayApi::createRoot()` for
  php-fpm / FrankenPHP workers.
- Composer name changed from `openpay/sdk` because that vendor is claimed on Packagist.
