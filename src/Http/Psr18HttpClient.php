<?php

declare(strict_types=1);

namespace Nowo\Openpay\Http;

use Nowo\Openpay\Exception\OpenpayException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * PSR-18 transport adapter (REQ-CS-007): plugs any PSR-18 client (Symfony Psr18Client, Guzzle, …) into the SDK.
 *
 * The SDK does not set timeouts on a PSR-18 client: configure connect/request timeouts, TLS verification and no redirects on the
 * client you pass (REQ-RUNTIME-001), e.g. `new Psr18Client(HttpClient::create(['timeout' => 30, 'max_redirects' => 0]))`.
 */
final readonly class Psr18HttpClient implements HttpClient
{
    /** Wraps a PSR-18 client with the PSR-17 factories used to build requests and bodies. */
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    /** Sends one JSON request through the PSR-18 client; throws OpenpayException on transport failure. */
    public function request(
        string $method,
        string $url,
        #[\SensitiveParameter]
        ?string $jsonBody = null,
        #[\SensitiveParameter]
        array $headers = [],
    ): HttpResponse {
        $methodUpper = strtoupper($method);
        $request = $this->requestFactory->createRequest('' === $methodUpper ? 'GET' : $methodUpper, $url)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        if (null !== $jsonBody) {
            $request = $request->withBody($this->streamFactory->createStream($jsonBody));
        }

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new OpenpayException('Openpay HTTP request failed: '.$e->getMessage(), 0, $e);
        }

        $responseHeaders = [];
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $responseHeaders[strtolower((string) $name)][] = $value;
            }
        }

        return new HttpResponse($response->getStatusCode(), (string) $response->getBody(), $responseHeaders);
    }
}
