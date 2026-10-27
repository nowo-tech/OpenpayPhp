# Configuration

| Setting | API |
|---------|-----|
| Merchant | `Credentials($merchantId, $privateKey, Country::Mx, sandbox: true, publicIp: '…')` |
| HTTP | Inject `HttpClient`; default `CurlHttpClient(5, 30)`; `Psr18HttpClient($psr18Client, $requestFactory, $streamFactory)` for any PSR-18 client |
| Worker | Use `Session::run()` once per request — do not reuse static credentials or a shared `Credentials` built from request data |

Under FrankenPHP worker mode with the kernel **not** reset, a shared `Credentials` / `Client` is safe only for values identical on every request (fixed merchant + fixed server IP). Per-request `publicIp` or multi-tenant merchants need a new `Credentials` each request.

See [SECURITY.md](SECURITY.md) and [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md).

## Timeouts

`new CurlHttpClient(connectTimeoutSeconds: 5, timeoutSeconds: 30)` (defaults) bounds every Openpay call; a timeout throws `OpenpayException`. It is the innermost deadline (REQ-RUNTIME-001): keep PHP `max_execution_time` above connect + total, the web server / proxy write timeout above PHP, and under FrankenPHP a `max_wait_time`. Raise them together. The demo values are in [DEMO-FRANKENPHP.md](DEMO-FRANKENPHP.md#timeouts-req-runtime-001).

## PSR-18 transport

`Psr18HttpClient` adapts any PSR-18 client (Symfony `Psr18Client`, Guzzle, …) plus PSR-17 request/stream factories to the SDK `HttpClient` interface. The SDK cannot set timeouts on a PSR-18 client: configure connect/total timeouts (same hierarchy as above) keep TLS peer/host verification enabled and disable redirects (`max_redirects: 0`, so the `Authorization` header is never replayed elsewhere) on the client you pass. A PSR-18 `ClientExceptionInterface` is rethrown as `OpenpayException`; its message may contain the request URL and the previous exception may hold the PSR-7 request with the `Authorization` header — see [SECURITY.md](SECURITY.md#attack-surface).

```php
$psr18 = new Symfony\Component\HttpClient\Psr18Client(
    Symfony\Component\HttpClient\HttpClient::create(['timeout' => 30, 'max_duration' => 30, 'max_redirects' => 0]),
);
$session = new Session($credentials, new Psr18HttpClient($psr18, $psr18, $psr18));
```

## Logging

Pass a PSR-3 logger to `new Client($credentials, $http, $logger)` or `new Session($credentials, $http, $logger)` to log API calls (method, status, Openpay error code / category / request id) and transport failures. Keys, payloads and card data are never logged — see [SECURITY.md](SECURITY.md#logging).

