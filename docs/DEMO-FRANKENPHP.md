# Demo notes (FrankenPHP)

The Symfony 8 demo under `demo/symfony8` runs on FrankenPHP (Caddy + PHP) and exercises the worker-safe `Session` API.

## Table of contents

- [Overview](#overview)
- [Start the demo in development](#start-the-demo-in-development)
- [Start the demo in production mode](#start-the-demo-in-production-mode)
- [Classic vs worker (`FRANKENPHP_MODE`)](#classic-vs-worker-frankenphp_mode)
- [Timeouts (REQ-RUNTIME-001)](#timeouts-req-runtime-001)
- [Troubleshooting](#troubleshooting)

## Overview

Worker-safe `Session` demo (`FRANKENPHP_MODE=worker` by default). The controller builds a new `Credentials` / `Session` per call and passes the client IP into `Credentials`. Without `OPENPAY_MERCHANT_ID` / `OPENPAY_PRIVATE_KEY`, it uses a mock `HttpClient`.

Requires Twig Inspector + Hot Reload in `require-dev` (REQ-DEMO-001).

Full worker audit: [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md).

## Start the demo in development

```bash
cd demo/symfony8
cp -n .env.example .env        # first run only
make up                        # docker compose up -d; entrypoint picks Caddyfile / Caddyfile.dev from FRANKENPHP_MODE
# http://localhost:8021/
```

For request (classic) mode and the simplest code reload, set `FRANKENPHP_MODE=classic` in `.env` and recreate the containers. `docker/php-dev.ini` sets `opcache.revalidate_freq=0`, and the Caddyfiles enable Mercure + `hot_reload` for Nowo Hot Reload.

Leave `OPENPAY_MERCHANT_ID` / `OPENPAY_PRIVATE_KEY` empty to use the mock transport; set sandbox credentials (`OPENPAY_SANDBOX=1`) to call the real Openpay sandbox.

## Start the demo in production mode

The production pattern is the worker Caddyfile (`php_server { worker { file /app/public/index.php; watch } }`), selected by the default `FRANKENPHP_MODE=worker`.

```bash
cd demo/symfony8
# .env: FRANKENPHP_MODE=worker, APP_ENV=prod, APP_DEBUG=0, a real APP_SECRET
make down && make up
docker compose exec php php bin/console cache:clear --env=prod
```

In a real deployment, bake the application into the image (no bind mount), keep worker mode, inject `OPENPAY_*` secrets from the platform (never commit them), and keep `OPENPAY_SANDBOX=0` only for live merchants.

## Classic vs worker (`FRANKENPHP_MODE`)

| Mode | Value | Caddyfile | PHP behaviour |
| --- | --- | --- | --- |
| Worker (default) | `FRANKENPHP_MODE=worker` | `docker/frankenphp/Caddyfile` | Long-lived workers; the kernel is reused between requests |
| Classic | `FRANKENPHP_MODE=classic` | `docker/frankenphp/Caddyfile.dev` | One PHP execution per request |

The entrypoint copies the matching Caddyfile at container start. `docker compose restart` does not reload `.env`; recreate the containers after changing the mode.

## Timeouts (REQ-RUNTIME-001)

The demo performs blocking outbound HTTP (Openpay REST) from a request, so the timeout hierarchy must hold: **cURL timeouts < PHP `max_execution_time` < Caddy / proxy timeouts**.

| Layer | Default | Where |
| --- | --- | --- |
| cURL connect | 5 s | `CurlHttpClient` `connectTimeoutSeconds` |
| cURL total | 30 s | `CurlHttpClient` `timeoutSeconds` |
| PHP execution | must be greater than 35 s when using the defaults | PHP ini / `set_time_limit` |
| Caddy / reverse proxy | must be greater than the PHP limit | Caddy `timeouts` / upstream proxy |

For interactive payment flows, pass shorter values to `new CurlHttpClient(connectTimeoutSeconds: …, timeoutSeconds: …)` so a slow Openpay host does not hold a worker thread (audit finding W-01).

## Troubleshooting

| Symptom | Cause | Fix |
| --- | --- | --- |
| PHP changes not visible | Worker mode keeps the kernel in memory | Use `FRANKENPHP_MODE=classic` in development or recreate the containers |
| `FRANKENPHP_MODE` change has no effect | `docker compose restart` keeps the old env | `make down && make up` |
| Port 8021 already in use | Another stack uses the port | Change `PORT` (and `DEFAULT_URI`) in `.env` and recreate |
| `Merchant id and private key are required.` | One of `OPENPAY_MERCHANT_ID` / `OPENPAY_PRIVATE_KEY` is set and the other empty | Set both, or leave both empty for the mock transport |
| Requests hang for ~35 s then fail | Openpay host unreachable; default cURL timeouts | Check network/DNS from the container; lower the cURL timeouts |
| HTTP 502 / container restarts | PHP fatal at boot (missing `vendor/`, bad config) | `docker compose logs php`; reinstall dependencies and clear the cache |
