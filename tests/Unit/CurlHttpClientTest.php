<?php

declare(strict_types=1);

namespace Nowo\Openpay\Tests\Unit;

use Nowo\Openpay\Exception\OpenpayException;
use Nowo\Openpay\Http\CurlHttpClient;
use Nowo\Openpay\Http\HttpResponse;
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
            $client->request('GET', 'http://'.$address.'/v1/merchant/charges');
            self::fail('A timeout was expected.');
        } catch (OpenpayException $e) {
            self::assertStringContainsString('Openpay HTTP request failed', $e->getMessage());
        } finally {
            fclose($server);
        }
        self::assertLessThan(10.0, microtime(true) - $started);
    }
}
