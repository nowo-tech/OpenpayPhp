# Security

See [.github/SECURITY.md](../.github/SECURITY.md).

1. Never commit private keys.
2. Prefer `Session` + new `Credentials` per request in FrankenPHP workers (especially when `publicIp` or merchant varies).
3. Keep cURL TLS verification enabled.
4. Use connect/request timeouts (`CurlHttpClient` defaults); shorten them on busy workers if needed.
5. Do not log card PAN/CVV or private keys.
6. Pin `^1.1` (or at least `^1.0`) in production Composer constraints.
7. See [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md) for scenario B (kernel not reset).
