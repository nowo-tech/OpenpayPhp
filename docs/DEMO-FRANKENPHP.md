# Demo notes (FrankenPHP)

```bash
make -C demo/symfony8 up
# http://localhost:8021/
```

Worker-safe `Session` demo. Without `OPENPAY_MERCHANT_ID` / `OPENPAY_PRIVATE_KEY`, the controller uses a mock `HttpClient`.

Requires Twig Inspector + Hot Reload in `require-dev` (REQ-DEMO-001).
