# Usage

```php
use Nowo\Openpay\Client;
use Nowo\Openpay\Credentials;
use Nowo\Openpay\Session;

$session = new Session(new Credentials(
    $merchantId,
    $privateKey,
    publicIp: $request->getClientIp() ?? '127.0.0.1',
));
$charge = $session->run(static function (Client $client) {
    return $client->charges->create([
        'method' => 'card',
        'source_id' => $tokenId,
        'amount' => 100.0,
        'description' => 'Demo',
        'device_session_id' => $deviceSessionId,
    ]);
});
```

Nested customer resources: `$client->customerCharges($id)`, `$client->customerCards($id)`.

## Table of contents

- [Webhooks](#webhooks)
- [Errors](#errors)
- [PSR-18 transport](#psr-18-transport)
- [FrankenPHP worker mode](#frankenphp-worker-mode)

## Webhooks

```php
$webhook = $session->run(static function (Client $client) {
    return $client->webhooks->add([
        'url' => 'https://merchant.example/openpay/hook',
        'user' => 'hook-user',
        'password' => 'hook-pass',
        'event_types' => [
            'verification',
            'charge.succeeded',
            'charge.failed',
            'charge.cancelled',
            'charge.created',
            'charge.refunded',
        ],
    ]);
});
// $webhook['id'], $webhook['status'] (e.g. verified)

$list = $session->client()->webhooks->getList();
$session->client()->webhooks->delete($webhook['id']);
```

`ResourceApi::add()` is an alias of `create()` (legacy Openpay SDK naming).

All resource methods return **arrays** (decoded JSON), not SDK objects.

## Errors

HTTP errors (status >= 400) throw `Nowo\Openpay\Exception\OpenpayApiException`, a subclass of `OpenpayException`:

```php
use Nowo\Openpay\Exception\OpenpayApiException;

try {
    $client->charges->create($payload);
} catch (OpenpayApiException $e) {
    $e->httpStatus;  // 402 (also $e->getCode())
    $e->errorCode;   // Openpay error_code, e.g. 3001 (card declined); null if absent
    $e->category;    // "request" | "internal" | "gateway"
    $e->description; // human-readable description
    $e->requestId;   // Openpay request_id, useful for support tickets
}
```

Other failures (invalid JSON, cURL errors, invalid credentials) throw `OpenpayException`.

## PSR-18 transport

Any PSR-18 client can carry the API calls instead of the default cURL transport (configure its timeouts and keep TLS verification on):

```php
use Nowo\Openpay\Http\Psr18HttpClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

$psr18 = new Psr18Client(HttpClient::create(['timeout' => 30, 'max_duration' => 30]));
$session = new Session($credentials, new Psr18HttpClient($psr18, $psr18, $psr18));
```

PSR-18 transport errors are rethrown as `OpenpayException`. See [CONFIGURATION.md](CONFIGURATION.md#psr-18-transport) and [PSR.md](PSR.md).

## FrankenPHP worker mode

When the kernel is **not** reset between requests:

1. Build a new `Credentials` + `Session` per HTTP request (especially if `publicIp` or merchant credentials vary by request/tenant).
2. Prefer `Session::run()` so the `Client` does not outlive the request.
3. Do not register a shared `Credentials` service filled from the first request’s data.

See [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md) and [CONFIGURATION.md](CONFIGURATION.md).
