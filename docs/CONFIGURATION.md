# Configuration

| Setting | API |
|---------|-----|
| Merchant | `Credentials($merchantId, $privateKey, Country::Mx, sandbox: true, publicIp: '…')` |
| HTTP | Inject `HttpClient`; default `CurlHttpClient(5, 30)` |
| Worker | Use `Session::run()` once per request — do not reuse static credentials or a shared `Credentials` built from request data |

Under FrankenPHP worker mode with the kernel **not** reset, a shared `Credentials` / `Client` is safe only for values identical on every request (fixed merchant + fixed server IP). Per-request `publicIp` or multi-tenant merchants need a new `Credentials` each request.

See [SECURITY.md](SECURITY.md) and [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md).
