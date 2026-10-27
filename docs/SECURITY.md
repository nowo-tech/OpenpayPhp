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
- [Release security checklist (12.4.1)](#release-security-checklist-1241)

## Scope

A framework-agnostic PHP client for the Openpay REST API (charges, customers, cards, tokens, webhooks). It exposes no HTTP endpoints, stores nothing and runs no subprocesses; the only I/O is outbound HTTPS to Openpay. Card data handling (tokenization in the browser, PCI scope) stays with the integrating application.

## Attack surface

| Input | Source | Notes |
| --- | --- | --- |
| `Credentials` (merchant id, private key, country, sandbox flag, public IP) | Application configuration / environment | Private key is a `#[\SensitiveParameter]` (redacted from stack traces) |
| Request payloads (`create`, `update`, `refund`, …) | Application code | JSON-encoded as-is; validation is the application's job |
| Resource ids (`get('…')`, `nested('…', …)`) | Application code (may come from user input) | URL-encoded with `rawurlencode()` before they are put in the path |
| HTTP responses | Openpay API | Decoded with `json_decode()`; errors become `OpenpayApiException` |
| `HttpClient` implementation | Application (optional) | Replaces the cURL transport; must keep TLS verification and timeouts |
| PSR-18 client passed to `Psr18HttpClient` | Application (optional) | Same rules: TLS verification on, connect/total timeouts set on the client (the SDK cannot set them); transport errors become `OpenpayException` (message of the PSR-18 exception, no headers) |

## Threat model

| Threat | Mitigation |
| --- | --- |
| Private key leak | Never in URLs, logs or exception messages; HTTP Basic header only; `#[\SensitiveParameter]` on the constructor |
| Cross-merchant data leak in long-lived workers | No static/global credentials: every `Client` is built from its own `Credentials`; `Session` builds a client per operation |
| Path injection through ids | Ids are URL-encoded; the API root is fixed by `Credentials::apiRoot()` |
| Man-in-the-middle | HTTPS endpoints only; cURL peer and host verification stay enabled (do not disable them in a custom transport) |
| Hanging requests pinning a PHP thread | Connect (5 s) and total (30 s) cURL timeouts, configurable per client |
| Error information disclosure | `OpenpayApiException` keeps the raw response body for debugging: do not render it to end users |

## Secrets and credentials

Keep the private key in environment variables or a secrets vault, never in the repository; prefer the sandbox key in development. Rotate the key in the Openpay dashboard if it is ever exposed.

## Timeouts and resource limits

`CurlHttpClient(int $connectTimeoutSeconds = 5, int $timeoutSeconds = 30)` bounds every call (REQ-RUNTIME-001). A timeout or transport failure throws `OpenpayException`. Keep PHP `max_execution_time` and the web server write timeout strictly greater than the total timeout; under FrankenPHP set `max_wait_time` so a busy pool answers 504 instead of queueing. See [CONFIGURATION.md](CONFIGURATION.md#timeouts) and [DEMO-FRANKENPHP.md](DEMO-FRANKENPHP.md).

## Logging

`Client` (and `Session`) accept an optional PSR-3 `LoggerInterface`:

| Event | Level | Context |
| --- | --- | --- |
| API call completed | `info` | `sdk: openpay`, `action: api_request`, `method`, `status` |
| API error (HTTP ≥ 400) | `warning` | same + `error_code`, `category`, `request_id` |
| Transport failure / timeout | `error` | same + `exception` (class name) |

**Never logged:** the private key, the `Authorization` header, request/response bodies, card numbers, CVV, customer personal data and full URLs.

## Dependencies

Runtime requirements are only `ext-curl`, `ext-json` and the PSR interface packages `psr/log`, `psr/http-client`, `psr/http-factory` and `psr/http-message`. `composer audit` runs in CI; Dependabot proposes updates. Security fixes ship as patch releases of the supported minor (see [.github/SECURITY.md](../.github/SECURITY.md)).

## Exposure and permissions

The SDK exposes nothing on its own. Webhook endpoints, if you register them with `webhooks->create()`, are routes of your application: authenticate them (Openpay webhooks can send HTTP basic credentials) and re-fetch the referenced object through the API before acting on it.

## FrankenPHP workers

Prefer `Session` + new `Credentials` per request in FrankenPHP workers (especially when `publicIp` or the merchant varies). See [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md) for scenario B (kernel not reset).

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
| **AI security audit (REQ-SEC-004)** | Latest grade still current; re-audit after API surface changes (e.g. webhooks, error handling). |

Record confirmation in the release PR or tag notes.
