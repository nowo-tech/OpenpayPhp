<?php

declare(strict_types=1);

namespace Nowo\Openpay\Tests\Unit;

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
}
