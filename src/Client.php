<?php

declare(strict_types=1);

namespace Nowo\Openpay;

use Nowo\Openpay\Api\ResourceApi;
use Nowo\Openpay\Exception\OpenpayException;
use Nowo\Openpay\Http\CurlHttpClient;
use Nowo\Openpay\Http\HttpClient;

/**
 * Openpay API client (no process-wide static credentials).
 *
 * Merchant-root resources: charges, customers, cards, tokens, webhooks.
 */
final readonly class Client
{
    public ResourceApi $charges;
    public ResourceApi $customers;
    public ResourceApi $cards;
    public ResourceApi $tokens;
    public ResourceApi $webhooks;

    public function __construct(
        private Credentials $credentials,
        private HttpClient $http = new CurlHttpClient(),
    ) {
        $this->charges = new ResourceApi($this, 'charges');
        $this->customers = new ResourceApi($this, 'customers');
        $this->cards = new ResourceApi($this, 'cards');
        $this->tokens = new ResourceApi($this, 'tokens');
        $this->webhooks = new ResourceApi($this, 'webhooks');
    }

    public function credentials(): Credentials
    {
        return $this->credentials;
    }

    public function customerCharges(string $customerId): ResourceApi
    {
        return $this->customers->nested($customerId, 'charges');
    }

    public function customerCards(string $customerId): ResourceApi
    {
        return $this->customers->nested($customerId, 'cards');
    }

    /**
     * @param array<string, mixed>|null $payload
     *
     * @return array<string, mixed>
     */
    public function request(string $method, string $relativePath, ?array $payload = null): array
    {
        $url = $this->credentials->apiRoot().'/'.ltrim($relativePath, '/');
        $body = null;
        if (null !== $payload) {
            $encoded = json_encode($payload, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
            // @codeCoverageIgnoreStart
            if (false === $encoded) {
                throw new OpenpayException('Unable to encode Openpay JSON payload.');
            }
            // @codeCoverageIgnoreEnd
            $body = $encoded;
        }

        $headers = [
            'Authorization' => 'Basic '.base64_encode($this->credentials->privateKey().':'),
            'User-Agent' => 'NowoOpenpayPhp/'.Version::VERSION,
            'X-Forwarded-For' => $this->credentials->publicIp(),
        ];

        $response = $this->http->request($method, $url, $body, $headers);
        if ($response->statusCode >= 400) {
            throw new OpenpayException(\sprintf('Openpay API error HTTP %d: %s', $response->statusCode, $response->body), $response->statusCode);
        }

        if ('' === $response->body) {
            return [];
        }

        $decoded = json_decode($response->body, true);
        if (!\is_array($decoded)) {
            throw new OpenpayException('Openpay API returned invalid JSON.');
        }

        /* @var array<string, mixed> $decoded */
        return $decoded;
    }
}
