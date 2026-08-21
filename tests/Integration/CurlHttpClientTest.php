<?php

declare(strict_types=1);

namespace Nowo\Openpay\Tests\Integration;

use Nowo\Openpay\Http\CurlHttpClient;
use PHPUnit\Framework\TestCase;

final class CurlHttpClientTest extends TestCase
{
    public function testPostJsonAgainstHttpsEcho(): void
    {
        $client = new CurlHttpClient(5, 20);
        $response = $client->request('POST', 'https://httpbin.org/post', '{"a":1}', ['X-Test' => 'nowo']);
        self::assertSame(200, $response->statusCode);
        self::assertStringContainsString('a', $response->body);
        self::assertNotEmpty($response->headers);
    }
}
