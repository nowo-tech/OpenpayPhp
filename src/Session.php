<?php

declare(strict_types=1);

namespace Nowo\Openpay;

use Nowo\Openpay\Http\CurlHttpClient;
use Nowo\Openpay\Http\HttpClient;

/**
 * Request-scoped helper: build a Client for one logical operation / HTTP request.
 * Prefer this over sharing a Client across FrankenPHP worker requests.
 */
final class Session
{
    public function __construct(
        private readonly Credentials $credentials,
        private readonly HttpClient $http = new CurlHttpClient(),
    ) {
    }

    /**
     * @template T
     * @param callable(Client): T $callback
     * @return T
     */
    public function run(callable $callback): mixed
    {
        $client = new Client($this->credentials, $this->http);

        return $callback($client);
    }

    public function client(): Client
    {
        return new Client($this->credentials, $this->http);
    }
}
