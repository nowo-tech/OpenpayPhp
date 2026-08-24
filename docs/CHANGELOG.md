# Changelog

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]


## [1.0.1] - 2026-08-24

### Changed

- **Tests:** expand coverage toward 100% (REQ-TEST-003).
- **Dependencies:** routine Composer/npm bumps (Dependabot).
- **Docs:** Spec Kit baseline refresh.

### Notes

- **No API or configuration changes** for integrators unless noted above.

[1.0.1]: https://github.com/nowo-tech/OpenpayPhp/releases/tag/v1.0.1

## [1.0.0] - 2026-08-21

### Added

- Clean-room MIT SDK `Nowo\Openpay\` (Client, Session, Credentials, Charges/Customers/Cards/Tokens)
- FrankenPHP worker-safe design (no static merchant credentials)
- Symfony 8 + FrankenPHP demo (`demo/symfony8`)
- PHPStan level 8 + FrankenPHP rulesets

### Removed

- Drop-in `Openpay\` fork of `openpay/sdk` and Composer `replace`

## [1.0.0] - 2026-08-21

### Added

- Clean-room MIT SDK `Nowo\Openpay\` (Client, Session, Credentials, Charges/Customers/Cards/Tokens)
- FrankenPHP worker-safe design (no static merchant credentials)
- Symfony 8 + FrankenPHP demo (`demo/symfony8`)
- PHPStan level 8 + FrankenPHP rulesets

### Removed

- Drop-in `Openpay\` fork of `openpay/sdk` and Composer `replace`
