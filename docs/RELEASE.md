# Release checklist

Use this checklist when cutting a new version.

> Current release: **3.2.1** (tag `v3.2.1`).
> Tags must match `v*` so `.github/workflows/release.yml` creates the GitHub Release.
> Older tags `3.1.1.1` and `3.2.0` have no `v` prefix (upstream-style).

## Before releasing

- [ ] `make release-check` (style, Rector dry-run, PHPStan, tests, coverage)
- [ ] After the release commit and **before** `git push`, run `make check-no-cursor-coauthor` again (REQ-GIT-001)
- [ ] Update [docs/CHANGELOG.md](CHANGELOG.md): move `[Unreleased]` to `[X.Y.Z] - YYYY-MM-DD`
- [ ] Update [docs/UPGRADING.md](UPGRADING.md) if the public API changed
- [ ] Bump `Openpay::VERSION` in `Openpay/Data/Openpay.php`

## Releasing

```bash
git tag -a v3.2.1 -m "Release v3.2.1"
git push origin master
git push origin v3.2.1
```

## After pushing

- [ ] Confirm GitHub Release from `release.yml`
- [ ] Packagist picks up the tag (`nowo-tech/openpay-php`)
- [ ] Keep `replace` of `openpay/sdk` at **3.1.1** unless the drop-in contract changed

## Security checklist (REQ-SEC-002)

- [ ] No secrets, merchant keys, or PAN/CVV in git, docs, or tests
- [ ] HTTP bodies with card data are not logged
- [ ] `composer audit` reviewed
