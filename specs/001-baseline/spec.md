# Feature Specification: Openpay PHP SDK baseline

**Status**: Shipped (`1.1.4`, reviewed 2026-10-20)  
**Input**: Clean-room Nowo Openpay REST client (`nowo-tech/openpay-php`).  
**Related docs**: [`docs/SPEC-DRIVEN-DEVELOPMENT.md`](../../docs/SPEC-DRIVEN-DEVELOPMENT.md), [`docs/USAGE.md`](../../docs/USAGE.md), [`docs/CONFIGURATION.md`](../../docs/CONFIGURATION.md)  
**Code inventory**: [`code-inventory.md`](code-inventory.md)

## Summary

MIT SDK `Nowo\Openpay\` for the public Openpay REST API (MX/CO/PE). Integrators get request-scoped `Credentials` / `Session` / `Client` objects (worker-safe under FrankenPHP with the kernel **not** reset between requests), merchant resources (charges, customers, cards, tokens, webhooks), an injectable HTTP transport with explicit timeouts, and typed API errors. There is no configuration root: the package is a plain PHP library.

## User Scenarios (`US-*`)

### US-01 — Create a charge (P1)

As a merchant app, I create a charge via `Client` / `Session` with an injectable HTTP transport.

**Acceptance**: **Given** valid `Credentials` and a transport, **When** I call `$client->charges->create($payload)`, **Then** the SDK sends `POST {apiRoot}/charges` with HTTP basic auth (private key) and returns the decoded JSON body.

### US-02 — FrankenPHP worker isolation (P1)

As an app on FrankenPHP worker mode (`reset_kernel` false / scenario B), I use `Session` + request-scoped `Credentials` without cross-request credential or `publicIp` leakage.

**Acceptance**: **Given** two consecutive requests with different merchants, **When** each builds its own `Session`, **Then** each `Client` only sees its own merchant id, key and client IP (no static state).

### US-03 — Typed API errors (P2)

As an integrator, I catch `OpenpayApiException` to read the HTTP status, Openpay `error_code`, category, description and request id.

**Acceptance**: **Given** the API answers HTTP ≥ 400 with an Openpay error body, **When** the request returns, **Then** `OpenpayApiException::fromResponse()` is thrown with those fields populated.

### US-04 — Customer-scoped resources and refunds (P2)

As an integrator, I manage a customer's cards and charges and refund a charge.

**Acceptance**: **Given** a customer id, **When** I call `$client->customerCards($id)->add(...)` or `$client->charges->refund($chargeId)`, **Then** the SDK targets `customers/{id}/cards` and `charges/{id}/refund`.

## Functional requirements (`FR-*`)

### Credentials and session (`FR-CRED-*`)

- **FR-CRED-001**: `Credentials` is immutable and request-scoped (merchant id, private key marked `#[SensitiveParameter]`, `Country`, sandbox flag, client public IP); empty merchant id or key, a non-alphanumeric merchant id or an invalid public IP (`FILTER_VALIDATE_IP`) throws `OpenpayException`.
- **FR-CRED-002**: `Country` selects sandbox / production API hosts for MX, CO and PE.
- **FR-CRED-003**: `Session` builds a new `Client` per logical operation (`run()` / `client()`); no process-wide merchant statics.

### Client and resources (`FR-API-*`)

- **FR-API-001**: `Client` exposes `charges`, `customers`, `cards`, `tokens`, `webhooks` and customer-scoped `customerCharges()` / `customerCards()`, and builds `{apiRoot}/{path}` requests with JSON bodies.
- **FR-API-002**: `ResourceApi` provides `create` / `add`, `get`, `getList`, `update`, `delete`, `refund` and `nested()` relative to the merchant root; ids must match `^[A-Za-z0-9_-]{1,64}$` and child resources `^[a-z_]+$`; payload parameters are `#[\SensitiveParameter]`.
- **FR-API-003**: `Version::VERSION` identifies the library release.

### HTTP transport (`FR-HTTP-*`)

- **FR-HTTP-001**: `HttpClient` is the transport contract; `HttpResponse` carries status code, body and headers.
- **FR-HTTP-002**: `CurlHttpClient` enforces connect (5 s) and total (30 s) timeouts, configurable per instance (REQ-RUNTIME-001), and uses the OS trust store for TLS.
- **FR-HTTP-003**: `Psr18HttpClient` adapts any PSR-18 client plus PSR-17 request/stream factories to `HttpClient`; PSR-18 client exceptions become `OpenpayException` (REQ-CS-007).

### Errors (`FR-ERR-*`)

- **FR-ERR-001**: Transport and encoding failures raise `OpenpayException`.
- **FR-ERR-002**: HTTP status ≥ 400 raises `OpenpayApiException` with status, error code, category, description, request id and the raw body (capped at 64 KiB, untrusted); the message is built from the sanitized structured fields only.

### Runtime (`FR-RUN-*`)

- **FR-RUN-001**: `src/` stays free of mutable/static request state; PHPStan FrankenPHP classic + worker-strict pass (see `docs/FRANKENPHP-WORKER-AUDIT.md`).

## Key entities

- **Credentials**: merchant identity + secret + region + sandbox flag + client IP, one instance per request/tenant.
- **Session / Client**: short-lived API entry points built from `Credentials` and an `HttpClient`.
- **Resource**: Openpay REST collection under the merchant root (`charges`, `customers`, `cards`, `tokens`, `webhooks`).

## Success criteria (`SC-*`)

- **SC-001**: **11/11** files tracked under `src/` are mapped in [`code-inventory.md`](code-inventory.md) (audit: `git ls-files src | wc -l`).
- **SC-002**: PHPStan level 8 + FrankenPHP worker-strict pass; PHPUnit line coverage ≥ 99% (currently 100%, 117/117).
- **SC-003**: No static merchant credentials anywhere in `src/` (worker audit verdict **Viable** for scenario B).
- **SC-004**: `make release-check` passes, including `check-no-cursor-coauthor`.

## Non-goals

- Redistributing or wrapping the official `openpay/sdk`.
- Framework integration (Symfony bundle, DI configuration); the demo is illustrative only.
- Process-wide static or global merchant configuration.

## Validation commands

```bash
make qa                  # PHP-CS-Fixer, PHPStan, PHPUnit (Docker)
composer test-coverage   # PHPUnit with coverage (gate >= 99% lines)
make release-check       # full pre-release gate (includes check-no-cursor-coauthor)
```
