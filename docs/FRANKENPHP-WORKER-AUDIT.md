# FrankenPHP worker mode audit (kernel not reset between requests)

| Field | Value |
|-------|-------|
| Package | `nowo-tech/openpay-php` (`library`, framework-agnostic SDK, no Symfony bundle / DI extension) |
| Audited revision | `v1.1.1` |
| Audit date | 2026-09-24 |
| Method | Manual review of every file under `src/` + PHPStan classic / **worker-strict** rulesets + demo controller review |
| **Verdict** | ✅ **Viable** — all classes are immutable, there is no static or global merchant state, cURL calls have explicit timeouts, and the library is safe when the Symfony kernel is **not** reset between FrankenPHP worker requests |

## Execution model assumed

FrankenPHP worker mode boots the Symfony kernel once per worker and serves many requests with the same container. This audit assumes the **strict** variant: the kernel is **not** rebooted between requests, so every shared service, static property and PHP global survives from one request to the next. Two scenarios are evaluated:

- **A — kernel not rebooted, `services_resetter` still runs:** services tagged `kernel.reset` (or implementing `ResetInterface`) are reset between requests.
- **B — no reset at all:** nothing is reset; any per-request state kept in a service leaks into the next request.

A library that is safe under **B** is safe under **A** and under classic mode / PHP-FPM.

The package registers no services itself. The "Shared" column below describes what happens when an application registers these classes as shared Symfony services.

## Summary

| Area | Status | Notes |
|------|--------|-------|
| Mutable state in shared services | ✅ | Every property is `readonly` / `final readonly class`; `Client`, `Credentials`, `Session`, `ResourceApi`, `CurlHttpClient` |
| Static properties / `static` locals | ✅ | None holding state; merchant credentials are never static |
| `ResetInterface` / `kernel.reset` coverage | ✅ N/A | Nothing to reset |
| Request / user / locale captured in services | ✅ | Nothing is read from a request; all values come from constructor arguments chosen by the integrator |
| Superglobals, `$_ENV`, `putenv`, `ini_set`, `setlocale`, timezone | ✅ | None used in `src/` (worker-strict clean) |
| Doctrine / EntityManager | ✅ N/A | No persistence |
| Output, headers, `exit`, shutdown functions | ✅ | None |
| Resources (files, sockets, cURL) held open | ✅ | One `CurlHandle` per call, local variable, released when `request()` returns |
| Memory growth across requests | ✅ | No caches or accumulating arrays |
| Blocking I/O and timeouts | ⚠️ Low | Explicit `CURLOPT_CONNECTTIMEOUT` (5 s) and `CURLOPT_TIMEOUT` (30 s), configurable; the default total wait is long for a worker thread |
| Third-party static state | ✅ | Only `ext-curl` and `ext-json`; no Composer runtime dependencies |
| PHPStan FrankenPHP rulesets | ✅ | `extension.neon`, `ruleset-classic.neon`, and `ruleset-worker-strict.neon` (includes worker) in `phpstan.neon.dist` |

Worker demo: `demo/symfony8` defaults to `FRANKENPHP_MODE=worker`. The demo controller builds a new `Credentials` / `Session` per call and passes the request client IP into `Credentials`.

## Services reviewed

| Service | Shared | Mutable state | Scenario A | Scenario B |
|---------|--------|---------------|------------|------------|
| `Nowo\Openpay\Client` | if the app registers it | none (`readonly`; includes `webhooks` ResourceApi) | ✅ | ✅ |
| `Nowo\Openpay\Credentials` | if the app registers it | none | ✅ | ✅ |
| `Nowo\Openpay\Session` | if the app registers it | none; creates a new `Client` per `run()` / `client()` | ✅ | ✅ |
| `Nowo\Openpay\Api\ResourceApi` | owned by `Client` | none; `nested()` / `add()` return or delegate without storing request data | ✅ | ✅ |
| `Nowo\Openpay\Http\CurlHttpClient` | if the app registers it | none (`readonly` timeouts; handle is a local variable) | ✅ | ✅ |

`HttpResponse` is a `readonly` value object created per call, `Country` is an enum, `Version` only holds a constant, and `OpenpayException` is a plain exception.

## Findings

### W-01 — Default cURL timeouts can hold a worker thread for up to 35 seconds (Low)

- **Where:** `src/Http/CurlHttpClient.php` (defaults `connectTimeoutSeconds = 5`, `timeoutSeconds = 30`).
- **Worker impact:** timeouts are explicit, so a request cannot hang forever. A slow Openpay host can still occupy a worker thread for up to 5 s + 30 s. No state leaks between requests.
- **Recommendation:** pass a `CurlHttpClient` with shorter values (for example 3 s / 15 s), and cap queued requests with FrankenPHP `max_wait_time`.

No other findings. **Scenario B (kernel never reset) is safe** when integrators follow the usage recommendations below.

## Usage recommendations in worker mode

- A shared `Client` or `Credentials` service is safe **only** for values identical on every request (fixed merchant + fixed server IP).
- Do not build a shared `Credentials` / `Client` from request data (`publicIp`, tenant merchant). Build a new `Credentials` + `Session` per request instead.
- Prefer `Session::run()` once per logical HTTP request.
- Custom `HttpClient` implementations must stay stateless.
- Configure shorter cURL timeouts for interactive payment flows (W-01).

## Re-audit triggers

Re-run this audit when a change adds: a static property or registry, a mutable property on `Client` / `Credentials` / `Session` / `ResourceApi`, a response or token cache, a persistent/shared cURL handle, retries with `sleep()`, or any read of `$_SERVER` / `$_ENV` / `getenv()` inside `src/`.
