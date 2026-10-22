<?php

declare(strict_types=1);

namespace Nowo\Openpay\Tests\Unit;

use Nowo\Openpay\Client;
use Nowo\Openpay\Credentials;
use Nowo\Openpay\Exception\OpenpayApiException;
use Nowo\Openpay\Exception\OpenpayException;
use Nowo\Openpay\Http\HttpClient;
use Nowo\Openpay\Http\HttpResponse;
use Nowo\Openpay\Session;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

/**
 * REQ-OBS-001: API calls are logged without the private key, payloads or card data.
 */
final class ClientLoggingTest extends TestCase
{
    public function testSuccessApiErrorAndTransportFailureAreLogged(): void
    {
        $logger = new RecordingLogger();
        $responses = [
            new HttpResponse(200, '{"id":"ch1"}'),
            new HttpResponse(402, '{"error_code":3001,"category":"card","description":"The card was declined","request_id":"req-9"}'),
            new OpenpayException('Openpay HTTP request failed: timeout'),
        ];
        $http = new class($responses) implements HttpClient {
            /** @param list<HttpResponse|OpenpayException> $responses */
            public function __construct(private array $responses)
            {
            }

            public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse
            {
                $next = array_shift($this->responses);
                if ($next instanceof OpenpayException) {
                    throw $next;
                }

                return $next ?? new HttpResponse(500, '');
            }
        };
        $client = (new Session(new Credentials('mid', 'sk_secret_key'), $http, $logger))->client();

        $client->charges->create(['amount' => 10, 'card_number' => '4111111111111111']);
        try {
            $client->charges->create(['amount' => 10]);
            self::fail('API error expected.');
        } catch (OpenpayApiException) {
        }
        try {
            $client->charges->get('ch1');
            self::fail('Transport error expected.');
        } catch (OpenpayException) {
        }

        self::assertSame(['info', 'warning', 'error'], array_column($logger->records, 0));
        self::assertSame(['sdk' => 'openpay', 'action' => 'api_request', 'method' => 'POST', 'status' => 200], $logger->records[0][2]);
        self::assertSame(3001, $logger->records[1][2]['error_code']);
        self::assertSame('card', $logger->records[1][2]['category']);
        self::assertSame('req-9', $logger->records[1][2]['request_id']);
        self::assertSame(OpenpayException::class, $logger->records[2][2]['exception']);
        $dump = (string) json_encode($logger->records);
        self::assertStringNotContainsString('sk_secret_key', $dump);
        self::assertStringNotContainsString('4111111111111111', $dump);
    }

    public function testClientWithoutLoggerStillWorks(): void
    {
        $http = new class implements HttpClient {
            public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse
            {
                return new HttpResponse(200, '');
            }
        };

        self::assertSame([], (new Client(new Credentials('mid', 'sk'), $http))->tokens->get('t1'));
    }
}

/**
 * In-memory PSR-3 logger for assertions.
 */
final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{0: mixed, 1: string, 2: array<mixed>}> */
    public array $records = [];

    /** @param array<mixed> $context */
    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = [$level, (string) $message, $context];
    }
}
