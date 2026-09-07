# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

The Composer package is [`nowo-tech/openpay-php`](https://packagist.org/packages/nowo-tech/openpay-php).

**Version lines on this package name:**

| Line | API | Packagist today |
| --- | --- | --- |
| **1.0.x** (`main`) | MIT clean-room `Nowo\Openpay\` | Installable (`v1.0.0`–`v1.0.2`) |
| **3.x** (former `master` fork) | Apache-2.0 drop-in `Openpay\` (`replace` `openpay/sdk` 3.1.1) | Tags were dropped from this VCS; Composer **cannot** resolve `3.1.1.1` / `3.2.0` / `v3.2.1` |

Consumers that still call `Openpay\Data\Openpay` / `OpenpayApi::createRoot()` (for example core-nowo) need the **3.x fork**, not `^1.0`. Until those tags are restored on a Packagist-connected ref, use the archived tree [`nowo-tech/OpenpayPhp-fork-archive`](https://github.com/nowo-tech/OpenpayPhp-fork-archive) branch `master` (commit `bb01997`, release **3.2.1**).

## Table of contents

- [[Unreleased]](#unreleased)
- [[1.0.2] - 2026-09-07](#102---2026-09-07)
- [[1.0.1] - 2026-08-24](#101---2026-08-24)
- [[1.0.0] - 2026-08-21](#100---2026-08-21)
- [[3.2.1] - 2026-08-21](#321---2026-08-21) (fork, not on Packagist)
- [[3.2.0] - 2026-08-21](#320---2026-08-21) (fork, not on Packagist)
- [[3.1.1.1] - 2026-08-21](#3111---2026-08-21) (fork, not on Packagist)

## [Unreleased]

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
