# Demo notes (FrankenPHP)

```bash
make -C demo/symfony8 up
# http://localhost:8021/
```

Worker-safe `Session` demo (`FRANKENPHP_MODE=worker` by default). The controller builds a new `Credentials` / `Session` per call and passes the client IP into `Credentials`. Without `OPENPAY_MERCHANT_ID` / `OPENPAY_PRIVATE_KEY`, it uses a mock `HttpClient`.

Requires Twig Inspector + Hot Reload in `require-dev` (REQ-DEMO-001).

Full worker audit: [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md).
