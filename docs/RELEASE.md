# Release

This checklist helps maintainers prepare and publish a release safely.

> Current release: **1.0.2** (tag `v1.0.2`), MIT clean-room `Nowo\Openpay\`.
> Tags must match `v*` so `.github/workflows/release.yml` creates the GitHub Release.

The 3.x Apache fork (`3.1.1.1`, `3.2.0`, `v3.2.1`) is **historical**. Those tags are not on this VCS; Packagist cannot install them. Do not retag 3.x onto `main` (wrong API). Restore them only from fork commits (`master` / `OpenpayPhp-fork-archive`).

## Table of contents

- [Pre-release](#pre-release)
- [Tag and publish](#tag-and-publish)
- [Post-release checks](#post-release-checks)
- [Restoring 3.x Packagist tags](#restoring-3x-packagist-tags)
- [Release history](#release-history)

## Pre-release

- [ ] `make release-check` (style, Rector dry-run, PHPStan, tests, coverage)
- [ ] After the release commit and **before** `git push`, run `make check-no-cursor-coauthor` again (REQ-GIT-001)
- [ ] Update [docs/CHANGELOG.md](CHANGELOG.md): move `[Unreleased]` to `[X.Y.Z] - YYYY-MM-DD`
- [ ] Update [docs/UPGRADING.md](UPGRADING.md) if the public API changed
- [ ] Bump `Nowo\Openpay\Version::VERSION` in `src/Version.php`
- [ ] Bump `extra.nowo-package-version` in `composer.json` if present

## Tag and publish

```bash
git tag -a v1.0.2 -m "Release v1.0.2"
git push origin main
git push origin v1.0.2
```

## Post-release checks

- [ ] Confirm GitHub Release from `release.yml`
- [ ] Packagist picks up the tag (`nowo-tech/openpay-php`)
- [ ] `composer require nowo-tech/openpay-php:^1.0` installs `Nowo\Openpay\` (not `Openpay\`)

## Restoring 3.x Packagist tags

Only from fork commits (not from `main`):

| Tag | Commit | Notes |
| --- | --- | --- |
| `3.1.1.1` | `f9153cab` | First Packagist fork; `replace` `openpay/sdk` 3.1.1 |
| `3.2.0` | `55bda407` | PHP 8.3+, `OpenpaySession`, HTTP transport |
| `v3.2.1` | `778e7d4` | Last fork release (`v*` prefix) |

```bash
git tag -a 3.1.1.1 f9153cab -m "Release 3.1.1.1"
git tag -a 3.2.0 55bda407 -m "Release 3.2.0"
git tag -a v3.2.1 778e7d4 -m "Release v3.2.1"
git push origin 3.1.1.1 3.2.0 v3.2.1
```

`3.2.1` is greater than `1.0.2` in Composer. After restoring tags, unpinned `composer require nowo-tech/openpay-php` will resolve to the fork. Integrators of the clean-room SDK must keep `^1.0`.

## Security checklist (REQ-SEC-002)

- [ ] No secrets, merchant keys, or PAN/CVV in git, docs, or tests
- [ ] HTTP bodies with card data are not logged
- [ ] `composer audit` reviewed

## Release history

| Version | Date | Notes |
| --- | --- | --- |
| [1.0.2](CHANGELOG.md#102---2026-09-07) | 2026-09-07 | Docs: restore 3.x history; Version identity `1.0.2` |
| [1.0.1](CHANGELOG.md#101---2026-08-24) | 2026-08-24 | Coverage / Dependabot / Spec Kit; `Nowo\Openpay\` |
| [1.0.0](CHANGELOG.md#100---2026-08-21) | 2026-08-21 | Clean-room MIT rewrite; dropped `Openpay\` fork and `replace` |
| [3.2.1](CHANGELOG.md#321---2026-08-21) | 2026-08-21 | Last drop-in fork; **not on Packagist** |
| [3.2.0](CHANGELOG.md#320---2026-08-21) | 2026-08-21 | Fork: Session, HTTP transport, PHP 8.3+; **not on Packagist** |
| [3.1.1.1](CHANGELOG.md#3111---2026-08-21) | 2026-08-21 | First Packagist fork (`configure`/`reset`/`createRoot`); **not on Packagist** |
