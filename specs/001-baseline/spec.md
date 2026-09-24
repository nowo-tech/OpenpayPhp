# Feature Specification: Openpay PHP SDK baseline

**Status**: Shipped (`1.1.1`)
**Input**: Clean-room Nowo Openpay REST client (`nowo-tech/openpay-php`).

## Summary

MIT SDK `Nowo\Openpay\` for public Openpay REST (MX/CO/PE). Worker-safe Session/Client under FrankenPHP with kernel **not** reset between requests. Resources: charges, customers, cards, tokens, **webhooks**.

## User Scenarios

### US-001 — Charge create (P1)

As a merchant app, I create a charge via `Client`/`Session` with injectable HTTP.

### US-002 — FrankenPHP worker (P1)

As an app on FrankenPHP worker mode (`reset_kernel` false / scenario B), I use `Session` + request-scoped `Credentials` without cross-request credential or `publicIp` leakage.

## Functional requirements

- FR-001: `Credentials` and `Session` are request-scoped (no process-wide merchant statics).
- FR-002: `CurlHttpClient` enforces connect/request timeouts (REQ-RUNTIME-001).
- FR-003: HTTP errors map to `OpenpayException` with status code.
- FR-004: Library `src/` stays free of mutable/static request state; PHPStan FrankenPHP classic + worker-strict pass (see `docs/FRANKENPHP-WORKER-AUDIT.md`).

## Success Criteria

- PHPStan 8 + FrankenPHP worker-strict, coverage ≥99%, no static merchant credentials.
- Worker audit verdict **Viable** for scenario B (kernel never reset).
