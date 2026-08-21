# Configuration

| Setting | API |
|---------|-----|
| Merchant | `Credentials($merchantId, $privateKey, Country::Mx, sandbox: true, publicIp: '…')` |
| HTTP | Inject `HttpClient`; default `CurlHttpClient(5, 30)` |
| Worker | Use `Session::run()` once per request — do not reuse static credentials |

See [SECURITY.md](SECURITY.md).
