# Changelog

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [1.0.0] - 2026-08-21

### Added

- Clean-room MIT SDK `Nowo\Openpay\` (Client, Session, Credentials, Charges/Customers/Cards/Tokens)
- FrankenPHP worker-safe design (no static merchant credentials)
- Symfony 8 + FrankenPHP demo (`demo/symfony8`)
- PHPStan level 8 + FrankenPHP rulesets

### Removed

- Drop-in `Openpay\` fork of `openpay/sdk` and Composer `replace`
