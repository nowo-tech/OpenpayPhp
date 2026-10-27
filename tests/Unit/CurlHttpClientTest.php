<?php

declare(strict_types=1);

namespace Nowo\Openpay\Tests\Unit;

use Nowo\Openpay\Exception\OpenpayException;
use Nowo\Openpay\Http\CurlHttpClient;
use Nowo\Openpay\Http\HttpResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CurlHttpClientTest extends TestCase
{
    public function testGetWithoutBodyParsesHeaders(): void
    {
        $client = new CurlHttpClient(5, 20);
        $response = $client->request('GET', 'https://httpbin.org/get');
        self::assertInstanceOf(HttpResponse::class, $response);
        self::assertSame(200, $response->statusCode);
        self::assertNotEmpty($response->headers);
    }

    public function testCustomTimeoutsAreStored(): void
    {
        $client = new CurlHttpClient(3, 15);
        $response = $client->request('GET', 'https://httpbin.org/get');
        self::assertSame(200, $response->statusCode);
    }

    /**
     * REQ-RUNTIME-001: an endpoint that accepts the connection but never answers must not pin the worker —
     * the cURL total timeout fires and the call fails with an OpenpayException.
     */
    public function testUnresponsiveEndpointTimesOut(): void
    {
        $server = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (false === $server) {
            self::markTestSkipped('Cannot open a local TCP socket: '.$errstr);
        }
        $address = stream_socket_get_name($server, false);
        self::assertIsString($address);

        $client = new CurlHttpClient(1, 1);
        $started = microtime(true);
        try {
            // https: the TLS handshake never completes, so only the total timeout can end the call.
            $client->request('GET', 'https://'.$address.'/v1/merchant/charges');
            self::fail('A timeout was expected.');
        } catch (OpenpayException $e) {
            self::assertStringContainsString('Openpay HTTP request failed', $e->getMessage());
        } finally {
            fclose($server);
        }
        $elapsed = microtime(true) - $started;
        self::assertGreaterThanOrEqual(0.9, $elapsed);
        self::assertLessThan(10.0, $elapsed);
    }

    public function testTlsAndRedirectOptionsAreExplicit(): void
    {
        self::assertSame([
            \CURLOPT_SSL_VERIFYPEER => true,
            \CURLOPT_SSL_VERIFYHOST => 2,
            \CURLOPT_FOLLOWLOCATION => false,
            \CURLOPT_PROTOCOLS => \CURLPROTO_HTTPS,
        ], CurlHttpClient::securityOptions());
    }

    public function testPlainHttpIsRefused(): void
    {
        $this->expectException(OpenpayException::class);
        $this->expectExceptionMessage('Openpay HTTP request failed');
        (new CurlHttpClient(1, 1))->request('GET', 'http://127.0.0.1:9/v1/merchant/charges');
    }

    public function testRedirectsAreNotFollowed(): void
    {
        $response = (new CurlHttpClient(5, 20))->request('GET', 'https://httpbin.org/redirect-to?url=https%3A%2F%2Fexample.com%2F&status_code=302');
        self::assertSame(302, $response->statusCode);
    }

    /**
     * @return iterable<string, array{0: array<string, string>}>
     */
    public static function injectedHeaders(): iterable
    {
        yield 'CR LF in value' => [['X-Test' => "a\r\nX-Evil: 1"]];
        yield 'LF in name' => [["X-Test\nX-Evil" => 'a']];
        yield 'NUL in value' => [['X-Test' => "a\0b"]];
    }

    /**
     * @param array<string, string> $headers
     */
    #[DataProvider('injectedHeaders')]
    public function testHeaderInjectionIsRejected(array $headers): void
    {
        $this->expectException(OpenpayException::class);
        $this->expectExceptionMessage('Invalid HTTP header');
        (new CurlHttpClient(1, 1))->request('GET', 'https://127.0.0.1:9/', null, $headers);
    }
}
