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
- [Using it from a DDD / hexagonal application](#using-it-from-a-ddd--hexagonal-application)
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

Other failures throw a subclass of `OpenpayException`: `OpenpayTransportException` (cURL / PSR-18 errors, timeouts), `OpenpayConfigurationException` (invalid credentials or IP), `OpenpayInvalidRequestException` (ids that do not look like Openpay ids, bad resource names, `refund()` outside charges) and `OpenpayInvalidResponseException` (invalid JSON). Catching `OpenpayException` still covers all of them.

## Charges interface

Services that only deal with charges can depend on `Nowo\Openpay\Api\ChargesApiInterface` (`create()`, `get()`, `getList()`, `refund()`). `$client->charges` and `$client->customerCharges($id)` implement it, and a test can pass any fake. The interface and the library stay free of framework types; a Symfony bundle or a DDD host wires it in its own container. The `OpenpayApiException` message is built from the structured fields; `responseBody` is untrusted (never render it).

## PSR-18 transport

Any PSR-18 client can carry the API calls instead of the default cURL transport (configure its timeouts and keep TLS verification on):

```php
use Nowo\Openpay\Http\Psr18HttpClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

$psr18 = new Psr18Client(HttpClient::create(['timeout' => 30, 'max_duration' => 30, 'max_redirects' => 0]));
$session = new Session($credentials, new Psr18HttpClient($psr18, $psr18, $psr18));
```

PSR-18 transport errors are rethrown as `OpenpayException`. See [CONFIGURATION.md](CONFIGURATION.md#psr-18-transport) and [PSR.md](PSR.md).

## Using it from a DDD / hexagonal application

The library has no entities, events or framework types, so both host kinds use it the same way. Standard hosts call
`Client` directly (`$client->charges->create(...)`). A DDD host keeps the SDK at the edge and exposes its own port.

- Domain: a host-owned `PaymentGateway` port with scalars and host value objects (amount in minor units, a charge id).
- Adapter (infrastructure): depends on `Nowo\Openpay\Api\ChargesApiInterface` (or on `Client`), builds the Openpay
  payload from the port call, maps the decoded array back to a host result, and translates `OpenpayApiException` /
  `OpenpayTransportException` into host exceptions. `responseBody` is untrusted: never render or log it.
- Wiring: build one `Client` (credentials from the host's secret store, an injected PSR-18 client via `Psr18HttpClient`)
  in the host container. It keeps no per-request state, so a long-lived worker shares it; use
  `$client->withPublicIp($ip)` per request for the end-user IP.
- Tests: fake `ChargesApiInterface` or `HttpClient`; no network.

```php
final class OpenpayPaymentGateway implements PaymentGateway
{
    public function __construct(private ChargesApiInterface $charges) {}

    public function charge(ChargeRequest $r): ChargeId
    {
        $data = $this->charges->create(['method' => 'card', 'source_id' => $r->tokenId, 'amount' => $r->amountMinor / 100, 'currency' => $r->currency, 'description' => $r->description]);

        return new ChargeId((string) $data['id']);
    }
}
```

## FrankenPHP worker mode

When the kernel is **not** reset between requests:

1. A shared `Client` service (fixed merchant) is fine: pass each request's end-user IP per call with
   `withPublicIp()`, which returns a new client and leaves the shared one unchanged:

   ```php
   $client->withPublicIp($request->getClientIp() ?? '127.0.0.1')->charges->create($payload);
   ```

2. When merchant credentials vary by request / tenant, build a new `Credentials` + `Session` per HTTP request, and
   prefer `Session::run()` so the `Client` does not outlive the request.
3. Do not register a shared `Credentials` service filled from the first request’s data.

See [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md) and [CONFIGURATION.md](CONFIGURATION.md).
