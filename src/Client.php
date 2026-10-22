<?php

declare(strict_types=1);

namespace Nowo\Openpay;

use Nowo\Openpay\Api\ResourceApi;
use Nowo\Openpay\Exception\OpenpayApiException;
use Nowo\Openpay\Exception\OpenpayException;
use Nowo\Openpay\Http\CurlHttpClient;
use Nowo\Openpay\Http\HttpClient;
use Psr\Log\LoggerInterface;

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

    /**
     * Creates a client for the given merchant credentials and HTTP transport (cURL by default).
     *
     * @param LoggerInterface|null $logger Logs every API call (method, status, Openpay error code/category/request id)
     *                                     and transport failures; never the private key, payloads or card data (REQ-OBS-001)
     */
    public function __construct(
        private Credentials $credentials,
        private HttpClient $http = new CurlHttpClient(),
        private ?LoggerInterface $logger = null,
    ) {
        $this->charges = new ResourceApi($this, 'charges');
        $this->customers = new ResourceApi($this, 'customers');
        $this->cards = new ResourceApi($this, 'cards');
        $this->tokens = new ResourceApi($this, 'tokens');
        $this->webhooks = new ResourceApi($this, 'webhooks');
    }

    /** Returns the merchant credentials this client signs requests with. */
    public function credentials(): Credentials
    {
        return $this->credentials;
    }

    /** Charges of one customer (customers/{id}/charges). */
    public function customerCharges(string $customerId): ResourceApi
    {
        return $this->customers->nested($customerId, 'charges');
    }

    /** Stored cards of one customer (customers/{id}/cards). */
    public function customerCards(string $customerId): ResourceApi
    {
        return $this->customers->nested($customerId, 'cards');
    }

    /**
     * @param array<string, mixed>|null $payload
     *
     * @return array<string, mixed>
     *
     * @throws OpenpayApiException when the API answers with HTTP >= 400 (error_code, category, request_id decoded)
     * @throws OpenpayException    when the response is not valid JSON
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

        $context = ['sdk' => 'openpay', 'action' => 'api_request', 'method' => strtoupper($method)];
        try {
            $response = $this->http->request($method, $url, $body, $headers);
        } catch (OpenpayException $e) {
            $this->logger?->error('Openpay API request failed (transport).', $context + ['exception' => $e::class]);

            throw $e;
        }
        if ($response->statusCode >= 400) {
            $error = OpenpayApiException::fromResponse($response->statusCode, $response->body);
            $this->logger?->warning('Openpay API returned an error.', $context + [
                'status' => $response->statusCode,
                'error_code' => $error->errorCode,
                'category' => $error->category,
                'request_id' => $error->requestId,
            ]);

            throw $error;
        }
        $this->logger?->info('Openpay API request completed.', $context + ['status' => $response->statusCode]);

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
