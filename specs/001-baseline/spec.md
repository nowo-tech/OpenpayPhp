# Feature Specification: Openpay PHP SDK baseline

**Status**: Shipped (`1.0.0`)
**Input**: Clean-room Nowo Openpay REST client (`nowo-tech/openpay-php`).

## Summary

MIT SDK `Nowo\Openpay\` for public Openpay REST (MX/CO/PE). Worker-safe Session/Client. Resources: charges, customers, cards, tokens.

## US-001 — Charge create

As a merchant app, I create a charge via `Client`/`Session` with injectable HTTP.

## Success Criteria

- PHPStan 8, coverage ≥99%, no static merchant credentials.
