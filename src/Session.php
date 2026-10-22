<?php

declare(strict_types=1);

namespace Nowo\Openpay;

use Nowo\Openpay\Http\CurlHttpClient;
use Nowo\Openpay\Http\HttpClient;
use Psr\Log\LoggerInterface;

/**
 * Request-scoped helper: build a Client for one logical operation / HTTP request.
 * Prefer this over sharing a Client across FrankenPHP worker requests.
 */
final readonly class Session
{
    /** Holds the credentials and transport used to build per-operation clients. */
    public function __construct(
        private Credentials $credentials,
        private HttpClient $http = new CurlHttpClient(),
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @template T
     *
     * @param callable(Client): T $callback
     *
     * @return T
     */
    public function run(callable $callback): mixed
    {
        $client = new Client($this->credentials, $this->http, $this->logger);

        return $callback($client);
    }

    /** Returns a new client for one operation (do not share it across worker requests). */
    public function client(): Client
    {
        return new Client($this->credentials, $this->http, $this->logger);
    }
}
