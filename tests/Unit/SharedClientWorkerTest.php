<?php

declare(strict_types=1);

namespace Nowo\Openpay\Tests\Unit;

use Nowo\Openpay\Client;
use Nowo\Openpay\Country;
use Nowo\Openpay\Credentials;
use Nowo\Openpay\Exception\OpenpayException;
use Nowo\Openpay\Http\HttpClient;
use Nowo\Openpay\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

/**
 * REQ-RUNTIME-002 worker test: one shared Client (as a service kept alive by a FrankenPHP worker, no reset between
 * requests) serves many end users. Each call carries only its own end user's IP, and the shared client keeps its own.
 */
final class SharedClientWorkerTest extends TestCase
{
    /** Calls per end user (2 × 1,000 = 2,000 calls on one client). */
    private const int ROUNDS = 1000;

    /** Fake private keys are built by concatenation so secret scanners never see a key-shaped literal. */
    private const string FAKE_KEY_PREFIX = 'sk_test_';

    public function testSharedClientSendsEachCallsOwnPublicIp(): void
    {
        $http = new class implements HttpClient {
            /** @var array<string, string> */
            public array $lastHeaders = [];

            public function request(string $method, string $url, #[\SensitiveParameter] ?string $jsonBody = null, #[\SensitiveParameter] array $headers = []): HttpResponse
            {
                $this->lastHeaders = $headers;

                return new HttpResponse(200, '{"id":"tr1"}');
            }
        };
        $shared = new Client(new Credentials('mid', self::FAKE_KEY_PREFIX.'fake', Country::Mx, true, '10.0.0.1'), $http);
        $users = ['203.0.113.7', '2001:db8::42'];
        $baseline = 0;

        for ($i = 0; $i < 2 * self::ROUNDS; ++$i) {
            $ip = $users[$i % 2];
            $shared->withPublicIp($ip)->charges->create(['amount' => 100 + $i % 2]);
            self::assertSame($ip, $http->lastHeaders['X-Forwarded-For'], "Call {$i} carries its own end user's IP.");

            // A call on the shared client itself never inherits the previous call's IP.
            $shared->charges->get('tr1');
            self::assertSame('10.0.0.1', $http->lastHeaders['X-Forwarded-For'], "Call {$i}: the shared client keeps its IP.");

            if (20 === $i) {
                gc_collect_cycles();
                $baseline = memory_get_usage();
            }
        }

        gc_collect_cycles();
        self::assertLessThan($baseline + 512 * 1024, memory_get_usage(), 'Memory stays flat over 4,000 calls.');
        self::assertSame('10.0.0.1', $shared->credentials()->publicIp());
    }

    public function testWithPublicIpKeepsTheMerchantAndTransport(): void
    {
        $credentials = new Credentials('mid', self::FAKE_KEY_PREFIX.'fake', Country::Co, false, '10.0.0.1');
        $copy = $credentials->withPublicIp('198.51.100.9');

        self::assertNotSame($credentials, $copy);
        self::assertSame('10.0.0.1', $credentials->publicIp());
        self::assertSame('198.51.100.9', $copy->publicIp());
        self::assertSame($credentials->merchantId(), $copy->merchantId());
        self::assertSame($credentials->privateKey(), $copy->privateKey());
        self::assertSame(Country::Co, $copy->country());
        self::assertFalse($copy->isSandbox());

        $client = new Client($credentials);
        $perUser = $client->withPublicIp('198.51.100.9');
        self::assertNotSame($client, $perUser);
        self::assertSame('198.51.100.9', $perUser->credentials()->publicIp());
        self::assertSame('10.0.0.1', $client->credentials()->publicIp());
    }

    public function testWithPublicIpRejectsAnInvalidIp(): void
    {
        $client = new Client(new Credentials('mid', self::FAKE_KEY_PREFIX.'fake'));

        $this->expectException(OpenpayException::class);
        $client->withPublicIp("203.0.113.7, 10.0.0.1\r\n");
    }
}
