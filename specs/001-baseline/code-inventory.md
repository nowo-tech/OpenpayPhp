# Code inventory — OpenpayPhp 1.1.1

**Last audited:** 2026-09-24 (FrankenPHP worker scenario B). **Coverage summary:** PHPUnit Lines gate ≥99% (`make release-check`).

| Unit | Path |
|------|------|
| Client | src/Client.php (`charges`, `customers`, `cards`, `tokens`, `webhooks`) |
| Session | src/Session.php |
| Credentials | src/Credentials.php |
| Country | src/Country.php |
| ResourceApi | src/Api/ResourceApi.php (`create`/`add`, CRUD, `refund`, `nested`) |
| CurlHttpClient | src/Http/CurlHttpClient.php |
| HttpClient / HttpResponse | src/Http/ |
| OpenpayException | src/Exception/OpenpayException.php |
| Version | src/Version.php |

Worker audit: [docs/FRANKENPHP-WORKER-AUDIT.md](../../docs/FRANKENPHP-WORKER-AUDIT.md).
