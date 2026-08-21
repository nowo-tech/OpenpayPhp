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
