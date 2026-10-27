# Security

Security guide for the Nowo Openpay PHP SDK (`nowo-tech/openpay-php`). The short public policy (supported versions, private reporting) lives in [.github/SECURITY.md](../.github/SECURITY.md).

## Table of contents

- [Scope](#scope)
- [Attack surface](#attack-surface)
- [Threat model](#threat-model)
- [Secrets and credentials](#secrets-and-credentials)
- [Timeouts and resource limits](#timeouts-and-resource-limits)
- [Logging](#logging)
- [Dependencies](#dependencies)
- [Exposure and permissions](#exposure-and-permissions)
- [FrankenPHP workers](#frankenphp-workers)
- [Payments and webhooks](#payments-and-webhooks)
- [Release security checklist (12.4.1)](#release-security-checklist-1241)
- [AI security audit](#ai-security-audit)

## Scope

A framework-agnostic PHP client for the Openpay REST API (charges, customers, cards, tokens, webhooks). It exposes no HTTP endpoints, stores nothing and runs no subprocesses; the only I/O is outbound HTTPS to Openpay. Card data handling (tokenization in the browser, PCI scope) stays with the integrating application.

## Attack surface

| Input | Source | Notes |
| --- | --- | --- |
| `Credentials` (merchant id, private key, country, sandbox flag, public IP) | Application configuration / environment | Private key is a `#[\SensitiveParameter]` (redacted from stack traces); merchant id must be alphanumeric; public IP must pass `FILTER_VALIDATE_IP` (single IPv4/IPv6, no CR/LF) |
| Request payloads (`create`, `update`, `refund`, …) | Application code | JSON-encoded as-is; validation is the application's job; `#[\SensitiveParameter]` on every payload / body / header parameter |
| Resource ids (`get('…')`, `nested('…', …)`) | Application code (may come from user input) | Must match `^[A-Za-z0-9_-]{1,64}$` (Openpay ids are short alphanumeric strings); `''`, `.`, `..`, `/`, `?` and control characters are rejected with `OpenpayException` before any request. Child resource names must match `^[a-z_]+$` |
| HTTP responses | Openpay API | Decoded with `json_decode()`; errors become `OpenpayApiException` whose message is built from the structured fields only (control characters stripped, 200-character cap); `responseBody` is untrusted and capped at 64 KiB |
| `HttpClient` implementation | Application (optional) | Replaces the cURL transport; must keep TLS verification and timeouts |
| PSR-18 client passed to `Psr18HttpClient` | Application (optional) | Same rules: TLS verification on, connect/total timeouts and **no redirects** set on the client (the SDK cannot set them). Transport errors become `OpenpayException` with the PSR-18 message, which may contain the request URL (merchant id, resource ids; never the key); the previous exception may hold the PSR-7 request **including the `Authorization` header** — do not serialize exception chains with their objects into logs or error trackers |

## Threat model

| Threat | Mitigation |
| --- | --- |
| Private key / card data leak | Never in URLs, logs, exception messages or stack-trace arguments (`#[\SensitiveParameter]` on the `Credentials` key and on every payload, JSON body and header parameter); HTTP Basic header only. Keep `zend.exception_ignore_args=On` in production (the PHP production default) |
| Cross-merchant data leak in long-lived workers | No static/global credentials: every `Client` is built from its own `Credentials`; `Session` builds a client per operation |
| Path injection through ids | Ids and child resource names are validated against strict patterns (no traversal, separators or control characters); the merchant id is alphanumeric; the API root is fixed by `Credentials::apiRoot()` |
| Header injection | `CurlHttpClient` rejects CR / LF / NUL in header names and values; the public IP is validated with `FILTER_VALIDATE_IP` |
| Man-in-the-middle / redirects | `CurlHttpClient` sets `CURLOPT_SSL_VERIFYPEER=true`, `CURLOPT_SSL_VERIFYHOST=2`, `CURLOPT_FOLLOWLOCATION=false` and `CURLOPT_PROTOCOLS=CURLPROTO_HTTPS` explicitly (HTTPS only, no redirects that could replay the `Authorization` header); keep the same settings in a custom or PSR-18 transport |
| Spoofed client IP | `publicIp` feeds Openpay anti-fraud: take it from Symfony `Request::getClientIp()` with `framework.trusted_proxies` / `trusted_headers` configured for your proxies only, never from a raw `X-Forwarded-For` header |
| Hanging requests pinning a PHP thread | Connect (5 s) and total (30 s) cURL timeouts, configurable per client |
| Error information disclosure | `OpenpayApiException::$responseBody` is untrusted network data kept for debugging (64 KiB cap): never render it to end users; show a generic message (as the demo does) |

## Secrets and credentials

Keep the private key in environment variables or a secrets vault, never in the repository; prefer the sandbox key in development. Rotate the key in the Openpay dashboard if it is ever exposed.

## Timeouts and resource limits

`CurlHttpClient(int $connectTimeoutSeconds = 5, int $timeoutSeconds = 30)` bounds every call (REQ-RUNTIME-001). A timeout or transport failure throws `OpenpayException`. Keep PHP `max_execution_time` and the web server write timeout strictly greater than the total timeout; under FrankenPHP set `max_wait_time` so a busy pool answers 504 instead of queueing. See [CONFIGURATION.md](CONFIGURATION.md#timeouts) and [DEMO-FRANKENPHP.md](DEMO-FRANKENPHP.md).

## Logging

`Client` (and `Session`) accept an optional PSR-3 `LoggerInterface`:

| Event | Level | Context |
| --- | --- | --- |
| API call completed | `info` | `sdk: openpay`, `action: api_request`, `method`, `status` |
| API error (HTTP ≥ 400) | `warning` | same + `error_code`, `category`, `request_id` (API strings capped at 128 bytes) |
| Transport failure / timeout | `error` | same + `exception` (class name) |

**Never logged:** the private key, the `Authorization` header, request/response bodies, card numbers, CVV, customer personal data and full URLs.

## Dependencies

Runtime requirements are only `ext-curl`, `ext-json` and the PSR interface packages `psr/log`, `psr/http-client`, `psr/http-factory` and `psr/http-message`. `composer audit` runs in CI; Dependabot proposes updates. Security fixes ship as patch releases of the supported minor (see [.github/SECURITY.md](../.github/SECURITY.md)).

## Exposure and permissions

The SDK exposes nothing on its own. Webhook endpoints, if you register them with `webhooks->create()`, are routes of your application: see [Payments and webhooks](#payments-and-webhooks).

## FrankenPHP workers

Prefer `Session` + new `Credentials` per request in FrankenPHP workers (especially when `publicIp` or the merchant varies). See [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md) for scenario B (kernel not reset).

## Payments and webhooks

- **Webhook URL:** register an `https://` URL only, with dedicated HTTP basic `user` / `password` (a random secret, not the API key).
- **Webhook authentication:** compare the received basic credentials with `hash_equals()` (constant time), reject everything else with 401, and **re-fetch** the referenced charge / object through the API before acting on it — never trust the webhook body alone.
- **Idempotency:** send your own `order_id` on charges. After a timeout or transport error the charge may or may not exist: look it up (by `order_id` / your records) before retrying — never blindly retry a timed-out charge.
- **Card data:** tokenize in the browser (Openpay.js) so card numbers and CVV never reach your server; the SDK only forwards what you pass and keeps it out of stack traces.

## Release security checklist (12.4.1)

Before tagging a release, confirm:

| Item | Notes |
| --- | --- |
| **SECURITY.md** | This document and `.github/SECURITY.md` are current and linked from the README. |
| **Secrets** | No keys or credentials in tracked files; demo uses sandbox placeholders only. |
| **TLS / timeouts** | cURL verification enabled; connect/total timeouts unchanged or documented. |
| **Logging** | No new log call writes keys, payloads or card data. |
| **Dependencies** | `composer audit` clean; issues triaged. |
| **Input / output** | Ids URL-encoded; no user-facing rendering of raw API bodies in the demo. |
| **AI security audit (REQ-SEC-004)** | Passes: **Pass (good) / Low**, 2026-10-27 ([AI security audit](#ai-security-audit), monorepo `BUNDLES_SECURITY_ANALYSIS.md` Appendix AL). Re-audit after API surface changes. |

Record confirmation in the release PR or tag notes.

## AI security audit

| Field | Value |
| --- | --- |
| Latest recorded audit | 2026-10-27 (re-audit of `main` at `9f19e66`, after 1.1.x webhooks / structured errors, 1.2.0 logging and the PSR-18 transport) |
| Grade | **Pass (good)** |
| Overall risk | **Low** |
| Method | Independent AI static review (Nowo monorepo security pass, REQ-SEC-004) |
| Record | Monorepo `BUNDLES_SECURITY_ANALYSIS.md`, Appendix AL |

| ID | Severity | Finding | Status |
| --- | --- | --- | --- |
| L1 | Low | Card payloads, JSON bodies and the `Authorization` header could appear in stack-trace arguments when `zend.exception_ignore_args=Off` | Fixed — `#[\SensitiveParameter]` on `HttpClient` / `CurlHttpClient` / `Psr18HttpClient::request` (`$jsonBody`, `$headers`), `Client::request` and `ResourceApi::create/add/update/refund` (`$payload`); reflection and trace tests |
| L2 | Low | Resource ids were only URL-encoded (`''`, `.`, `..`, control characters accepted); child resource name not validated | Fixed — ids `^[A-Za-z0-9_-]{1,64}$`, child resources `^[a-z_]+$` |
| L3 | Low | `OpenpayApiException` message embedded the raw body; body and logged API strings unbounded | Fixed — message from sanitized structured fields (200-character cap), `responseBody` capped at 64 KiB and documented as untrusted, log strings capped at 128 bytes |
| L4 | Low | `publicIp` / merchant id unvalidated; CR/LF/NUL not rejected in cURL headers | Fixed — `FILTER_VALIDATE_IP`, merchant id `^[A-Za-z0-9]+$`, header CR/LF/NUL rejected; trusted-proxy guidance |
| I1 | Info | TLS / redirect / protocol cURL options relied on defaults | Fixed — set explicitly (verify peer + host, no redirects, HTTPS only) + tests |
| I2 | Info | PSR-18 example without redirect limit; URL / PSR-7 request in exception chain undocumented | Fixed — `max_redirects: 0` in examples; documented above |
| I3 | Info | Webhook advice lacked constant-time comparison and https-only URL | Fixed — [Payments and webhooks](#payments-and-webhooks) |
| I4 | Info | No guidance on `order_id` / retrying timed-out charges | Fixed — [Payments and webhooks](#payments-and-webhooks) |
| I5 | Info | Demo silently fell back to MX for an unknown `OPENPAY_COUNTRY` | Fixed — configuration error |
| I6 | Info | Demo did not catch transport errors; port bound on all interfaces | Fixed — generic 502 page; port bound to `127.0.0.1` |
| I7 | Info | `Version::VERSION` / `extra.nowo-package-version` stale (1.1.4 in v1.2.0) | Fixed — set to 1.2.0; `make bump-version`, `make check-version` in `release-check`, tag check in `release.yml` |

**Residual risks (host responsibility):**

- Timeouts, TLS verification and redirect policy of an injected PSR-18 client (or custom `HttpClient`) are configured by the host.
- Keep `zend.exception_ignore_args=On` in production; do not ship exception chains with their objects (PSR-7 requests) to logs or error trackers.
- `publicIp` is only as trustworthy as the host's trusted-proxy configuration.
- Webhook endpoints must authenticate (constant-time compare) and re-fetch objects through the API.
- Browser tokenization and PCI DSS scope stay with the integrating application.
- Never render `OpenpayApiException::$responseBody` to end users.
