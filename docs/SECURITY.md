# Security

See [.github/SECURITY.md](../.github/SECURITY.md).

1. Never commit private keys.
2. Prefer `Session` per request in FrankenPHP workers.
3. Keep cURL TLS verification enabled.
4. Use connect/request timeouts (`CurlHttpClient` defaults).
5. Do not log card PAN/CVV or private keys.
6. Pin `^1.0` in production Composer constraints.
