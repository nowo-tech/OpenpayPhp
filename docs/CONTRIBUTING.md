# Contributing

Thank you for considering contributing to the Nowo Openpay PHP SDK.

## Table of contents

- [Code of Conduct](#code-of-conduct)
- [Maintainer](#maintainer)
- [Development setup](#development-setup)
- [Pull requests](#pull-requests)
- [Coding standards](#coding-standards)
- [Cursor Agent](#cursor-agent)
- [Igor worker audit (REQ-CS-008)](#igor-worker-audit-req-cs-008)
- [AI co-author trailers](#ai-co-author-trailers)

## Code of Conduct

This project follows the [Contributor Covenant Code of Conduct](../CODE_OF_CONDUCT.md).
By participating, you are expected to uphold it. Please report unacceptable
behavior to **hectorfranco@nowo.tech**.

## Maintainer

This project is maintained by [Héctor Franco Aceituno](https://github.com/HecFranco)
at [Nowo.tech](https://nowo.tech).

## Development setup

```bash
git clone git@github.com:nowo-tech/OpenpayPhp.git
cd OpenpayPhp
make setup-hooks
make up
make test
```

Without Docker: PHP 8.3+, Composer, `composer install`, `composer test`.

Install git hooks with `make setup-hooks` before committing. Run
`make check-no-cursor-coauthor` before every push. If CI `git-hygiene`
fails on historical trailers, run `make strip-cursor-coauthor-from-history`
(then force-push only with maintainer approval).

## Pull requests

1. Fork the repository.
2. Create a feature branch from `main`.
3. Run `make release-check` (or at least `composer test` and `composer cs-check`).
4. Open a PR using the template.

## Coding standards

- PSR-12 via PHP-CS-Fixer (`make cs-fix`)
- `declare(strict_types=1);` on every PHP file
- PHPDoc and comments in **English**
- PHPStan (FrankenPHP classic + worker rulesets)

## Cursor Agent

Contributors using Cursor Agent on this repository should enable these user-level settings (REQ-IDE-006):

1. **Submit with Ctrl + Enter** (macOS: **⌘ + Enter**) — Cursor Settings → Agents / Chat. Enter inserts a newline; Ctrl/⌘+Enter submits.
2. **Queue follow-ups** in user `settings.json` (Linux `~/.config/Cursor/User/settings.json`, Windows `%APPDATA%\Cursor\User\settings.json`, macOS `~/Library/Application Support/Cursor/User/settings.json`):

```json
{
  "cursor.composer.queueMessageDefaultBehavior": "queue"
}
```

Do not use `"steer"` as the default: it interrupts a running agent turn.

## Igor worker audit (REQ-CS-008)

Run `make igor` (or `composer igor`) before release. Igor audits package `src/` for FrankenPHP worker-state issues. It is require-dev only.

## AI co-author trailers

Do **not** add AI co-author trailers to commit messages: neither `Co-authored-by: Cursor` / `cursoragent@cursor.com` nor `Co-Authored-By: Claude …` / `noreply@anthropic.com` (REQ-GIT-001). Run `make setup-hooks` so the `commit-msg` hook strips them.
