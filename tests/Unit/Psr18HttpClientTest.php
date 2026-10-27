<?php

declare(strict_types=1);

namespace Nowo\Openpay\Tests\Unit;

use Nowo\Openpay\Client;
use Nowo\Openpay\Credentials;
use Nowo\Openpay\Exception\OpenpayException;
use Nowo\Openpay\Http\Psr18HttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * REQ-CS-007: any PSR-18 client can serve as the SDK transport.
 */
final class Psr18HttpClientTest extends TestCase
{
    public function testSendsJsonRequestAndMapsResponse(): void
    {
        $psr18 = new RecordingPsr18Client(new Response(201, ['X-Request-Id' => ['a', 'b']], '{"id":"cus_1"}'));
        $factory = new Psr17Factory();
        $client = new Client(new Credentials('mid', 'sk_test'), new Psr18HttpClient($psr18, $factory, $factory));

        self::assertSame(['id' => 'cus_1'], $client->customers->create(['name' => 'Ana']));

        $request = $psr18->lastRequest;
        self::assertNotNull($request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://sandbox-api.openpay.mx/v1/mid/customers', (string) $request->getUri());
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        self::assertSame('Basic '.base64_encode('sk_test:'), $request->getHeaderLine('Authorization'));
        self::assertSame('{"name":"Ana"}', (string) $request->getBody());
    }

    public function testResponseHeadersAreLowerCasedListsAndEmptyMethodDefaultsToGet(): void
    {
        $psr18 = new RecordingPsr18Client(new Response(200, ['X-Request-Id' => ['a', 'b']], ''));
        $factory = new Psr17Factory();

        $response = (new Psr18HttpClient($psr18, $factory, $factory))->request('', 'https://example.test/x');

        self::assertSame(200, $response->statusCode);
        self::assertSame('', $response->body);
        self::assertSame(['x-request-id' => ['a', 'b']], $response->headers);
        self::assertNotNull($psr18->lastRequest);
        self::assertSame('GET', $psr18->lastRequest->getMethod());
        self::assertSame('', (string) $psr18->lastRequest->getBody());
    }

    public function testClientExceptionBecomesOpenpayException(): void
    {
        $psr18 = new RecordingPsr18Client(new Psr18TransportException('Connection timed out'));
        $factory = new Psr17Factory();

        try {
            (new Psr18HttpClient($psr18, $factory, $factory))->request('GET', 'https://example.test/x');
            self::fail('Expected OpenpayException');
        } catch (OpenpayException $e) {
            self::assertSame('Openpay HTTP request failed: Connection timed out', $e->getMessage());
            self::assertInstanceOf(Psr18TransportException::class, $e->getPrevious());
        }
    }
}

/**
 * PSR-18 test double that records the last request and returns (or throws) a fixed outcome.
 */
final class RecordingPsr18Client implements ClientInterface
{
    public ?RequestInterface $lastRequest = null;

    public function __construct(private readonly ResponseInterface|ClientExceptionInterface $outcome)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;
        if ($this->outcome instanceof ClientExceptionInterface) {
            throw $this->outcome;
        }

        return $this->outcome;
    }
}

/**
 * PSR-18 client exception used by the tests.
 */
final class Psr18TransportException extends \RuntimeException implements ClientExceptionInterface
{
}
