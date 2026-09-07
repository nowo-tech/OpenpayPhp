# Feature Specification: Openpay PHP SDK baseline

**Status**: Shipped (`1.0.2`)
**Input**: Clean-room Nowo Openpay REST client (`nowo-tech/openpay-php`).

## Summary

MIT SDK `Nowo\Openpay\` for public Openpay REST (MX/CO/PE). Worker-safe Session/Client. Resources: charges, customers, cards, tokens.

## User Scenarios

### US-001 — Charge create (P1)

As a merchant app, I create a charge via `Client`/`Session` with injectable HTTP.

## Functional requirements

- FR-001: `Credentials` and `Session` are request-scoped (no process-wide merchant statics).
- FR-002: `CurlHttpClient` enforces connect/request timeouts (REQ-RUNTIME-001).
- FR-003: HTTP errors map to `OpenpayException` with status code.

## Success Criteria

- PHPStan 8, coverage ≥99%, no static merchant credentials.
