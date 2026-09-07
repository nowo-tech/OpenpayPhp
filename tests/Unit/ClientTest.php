<?php

declare(strict_types=1);

namespace Nowo\Openpay\Tests\Unit;

use Nowo\Openpay\Client;
use Nowo\Openpay\Country;
use Nowo\Openpay\Credentials;
use Nowo\Openpay\Exception\OpenpayException;
use Nowo\Openpay\Http\HttpClient;
use Nowo\Openpay\Http\HttpResponse;
use Nowo\Openpay\Session;
use Nowo\Openpay\Version;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testCustomerCardsNested(): void
    {
        $urls = [];
        $http = new class($urls) implements HttpClient {
            /** @param list<string> $urls */
            public function __construct(private array &$urls)
            {
            }

            public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse
            {
                $this->urls[] = $method.' '.$url;

                return new HttpResponse(200, '{"id":"card1"}');
            }
        };
        $client = new Client(new Credentials('mid', 'sk'), $http);
        $card = $client->customerCards('cus_1')->get('card1');
        self::assertSame('card1', $card['id']);
        self::assertTrue(
            (bool) array_filter($urls, static fn (string $u): bool => str_contains($u, '/customers/cus_1/cards/card1'))
        );
    }

    public function testCreatesChargeViaFakeHttp(): void
    {
        $http = new class implements HttpClient {
            public string $lastUrl = '';
            public string $lastMethod = '';
            /** @var array<string, string> */
            public array $lastHeaders = [];

            public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse
            {
                $this->lastMethod = $method;
                $this->lastUrl = $url;
                $this->lastHeaders = $headers;

                return new HttpResponse(200, '{"id":"tr123","amount":100}');
            }
        };

        $creds = new Credentials('mid', 'sk_test', Country::Mx, true, '203.0.113.10');
        $client = new Client($creds, $http);
        $charge = $client->charges->create(['amount' => 100, 'method' => 'card']);

        self::assertSame('tr123', $charge['id']);
        self::assertSame('POST', $http->lastMethod);
        self::assertStringContainsString('sandbox-api.openpay.mx/v1/mid/charges', $http->lastUrl);
        self::assertArrayHasKey('Authorization', $http->lastHeaders);
        self::assertStringStartsWith('Basic ', $http->lastHeaders['Authorization']);
        self::assertSame('203.0.113.10', $http->lastHeaders['X-Forwarded-For']);
    }

    public function testCustomerNestedChargesAndSession(): void
    {
        $http = new class implements HttpClient {
            public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse
            {
                return new HttpResponse(200, '{"id":"ch1"}');
            }
        };
        $creds = new Credentials('mid', 'sk_test', Country::Co, false);
        self::assertStringContainsString('api.openpay.co', $creds->baseUrl());

        $session = new Session($creds, $http);
        $result = $session->run(static function (Client $client): array {
            return $client->customerCharges('cus_1')->create(['amount' => 10]);
        });
        self::assertSame('ch1', $result['id']);

        $list = $session->client()->customers->getList(['limit' => 5]);
        self::assertSame('ch1', $list['id']);
    }

    public function testHttpErrorThrows(): void
    {
        $http = new class implements HttpClient {
            public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse
            {
                return new HttpResponse(401, '{"description":"Unauthorized"}');
            }
        };
        $client = new Client(new Credentials('mid', 'sk'), $http);
        $this->expectException(OpenpayException::class);
        $client->tokens->get('tok_1');
    }

    public function testRefundAndCrudHelpers(): void
    {
        $urls = [];
        $http = new class($urls) implements HttpClient {
            /** @param list<string> $urls */
            public function __construct(private array &$urls)
            {
            }

            public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse
            {
                $this->urls[] = $method.' '.$url;

                return new HttpResponse(200, '{"ok":true}');
            }
        };
        $client = new Client(new Credentials('mid', 'sk', Country::Pe), $http);
        $client->cards->get('card1');
        $client->customers->update('c1', ['name' => 'Ada']);
        $client->customers->delete('c1');
        $client->charges->refund('tr1', ['amount' => 1]);
        $client->customerCards('c1')->getList();

        self::assertTrue(
            (bool) array_filter($urls, static fn (string $u): bool => str_contains($u, '/charges/tr1/refund'))
        );
        self::assertSame('1.0.2', Version::VERSION);
    }

    public function testEmptyCredentialsRejected(): void
    {
        $this->expectException(OpenpayException::class);
        new Credentials('', 'sk');
    }

    public function testRefundOnNonChargesThrows(): void
    {
        $http = new class implements HttpClient {
            public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse
            {
                return new HttpResponse(200, '{}');
            }
        };
        $client = new Client(new Credentials('mid', 'sk'), $http);
        $this->expectException(OpenpayException::class);
        $client->customers->refund('x');
    }

    public function testInvalidJsonThrows(): void
    {
        $http = new class implements HttpClient {
            public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse
            {
                return new HttpResponse(200, 'not-json');
            }
        };
        $client = new Client(new Credentials('mid', 'sk'), $http);
        $this->expectException(OpenpayException::class);
        $client->charges->get('x');
    }

    public function testEmptyBodyReturnsEmptyArray(): void
    {
        $http = new class implements HttpClient {
            public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse
            {
                return new HttpResponse(204, '');
            }
        };
        $client = new Client(new Credentials('mid', 'sk'), $http);
        self::assertSame([], $client->charges->delete('x'));
        self::assertSame('mid', $client->credentials()->merchantId());
    }

    public function testCredentialAccessorsAndMxSandbox(): void
    {
        $c = new Credentials('mid', 'sk', Country::Mx, true, '1.2.3.4');
        self::assertSame('mid', $c->merchantId());
        self::assertSame('sk', $c->privateKey());
        self::assertSame(Country::Mx, $c->country());
        self::assertTrue($c->isSandbox());
        self::assertSame('1.2.3.4', $c->publicIp());
        self::assertStringContainsString('sandbox-api.openpay.mx', $c->apiRoot());
    }
}
