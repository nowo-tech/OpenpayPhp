# Usage

```php
use Nowo\Openpay\Client;
use Nowo\Openpay\Credentials;
use Nowo\Openpay\Session;

$session = new Session(new Credentials($merchantId, $privateKey));
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