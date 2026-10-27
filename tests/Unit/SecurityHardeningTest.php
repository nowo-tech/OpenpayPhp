<?php

declare(strict_types=1);

namespace Nowo\Openpay\Tests\Unit;

use Nowo\Openpay\Api\ResourceApi;
use Nowo\Openpay\Client;
use Nowo\Openpay\Credentials;
use Nowo\Openpay\Exception\OpenpayApiException;
use Nowo\Openpay\Exception\OpenpayException;
use Nowo\Openpay\Http\CurlHttpClient;
use Nowo\Openpay\Http\HttpClient;
use Nowo\Openpay\Http\HttpResponse;
use Nowo\Openpay\Http\Psr18HttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Hardening from the 2026-10-27 AI security audit (findings L1–L4).
 */
final class SecurityHardeningTest extends TestCase
{
    /**
     * @return iterable<string, array{0: class-string, 1: string, 2: list<string>}>
     */
    public static function sensitiveParameters(): iterable
    {
        yield 'HttpClient::request' => [HttpClient::class, 'request', ['jsonBody', 'headers']];
        yield 'CurlHttpClient::request' => [CurlHttpClient::class, 'request', ['jsonBody', 'headers']];
        yield 'Psr18HttpClient::request' => [Psr18HttpClient::class, 'request', ['jsonBody', 'headers']];
        yield 'Client::request' => [Client::class, 'request', ['payload']];
        yield 'ResourceApi::create' => [ResourceApi::class, 'create', ['payload']];
        yield 'ResourceApi::add' => [ResourceApi::class, 'add', ['payload']];
        yield 'ResourceApi::update' => [ResourceApi::class, 'update', ['payload']];
        yield 'ResourceApi::refund' => [ResourceApi::class, 'refund', ['payload']];
    }

    /**
     * L1: card data and the Authorization header are redacted from stack traces.
     *
     * @param class-string $class
     * @param list<string> $expected
     */
    #[DataProvider('sensitiveParameters')]
    public function testPayloadAndHeaderParametersAreSensitive(string $class, string $method, array $expected): void
    {
        $sensitive = [];
        foreach ((new \ReflectionMethod($class, $method))->getParameters() as $parameter) {
            if ([] !== $parameter->getAttributes(\SensitiveParameter::class)) {
                $sensitive[] = $parameter->getName();
            }
        }

        self::assertSame($expected, $sensitive);
    }

    /**
     * L1: with exception arguments enabled, neither a transport error nor an API error exposes
     * the private key, a card number or a CVV in its stack-trace arguments.
     */
    public function testStackTraceArgumentsDoNotLeakSecrets(): void
    {
        $previous = ini_set('zend.exception_ignore_args', '0');
        try {
            // Obvious fake values built at runtime (no real-looking secrets in the repository).
            $key = 'REDACTED-TEST-'.bin2hex(random_bytes(4));
            $pan = '4'.str_repeat('1', 15);
            $cvv = (string) random_int(100, 999);
            $payload = ['card_number' => $pan, 'cvv2' => $cvv, 'amount' => 10];

            $failing = new class implements HttpClient {
                public function request(
                    string $method,
                    string $url,
                    #[\SensitiveParameter]
                    ?string $jsonBody = null,
                    #[\SensitiveParameter]
                    array $headers = [],
                ): HttpResponse {
                    throw new OpenpayException('Openpay HTTP request failed: timeout');
                }
            };
            $erroring = new readonly class implements HttpClient {
                public function request(
                    string $method,
                    string $url,
                    #[\SensitiveParameter]
                    ?string $jsonBody = null,
                    #[\SensitiveParameter]
                    array $headers = [],
                ): HttpResponse {
                    return new HttpResponse(402, '{"error_code":3001,"category":"card","description":"The card was declined"}');
                }
            };

            foreach ([$failing, $erroring] as $http) {
                $client = new Client(new Credentials('mid', $key), $http);
                try {
                    $client->charges->create($payload);
                    self::fail('An exception was expected.');
                } catch (OpenpayException $e) {
                    $dump = $this->flattenTraceArgs($e->getTrace());
                    self::assertStringContainsString('SensitiveParameterValue', $dump, 'Arguments must be captured for this test to be meaningful.');
                    self::assertStringNotContainsString($key, $dump);
                    self::assertStringNotContainsString(base64_encode($key.':'), $dump);
                    self::assertStringNotContainsString($pan, $dump);
                    self::assertStringNotContainsString('"cvv2":"'.$cvv.'"', $dump);
                    self::assertStringNotContainsString($key, $e->getMessage());
                    self::assertStringNotContainsString($pan, $e->getMessage());
                }
            }

            // A real transport failure (refused plain-HTTP URL) also keeps the body and headers out of the trace.
            try {
                (new CurlHttpClient(1, 1))->request('POST', 'http://127.0.0.1:9/', '{"card_number":"'.$pan.'"}', ['Authorization' => 'Basic '.$key]);
                self::fail('An exception was expected.');
            } catch (OpenpayException $e) {
                $dump = $this->flattenTraceArgs($e->getTrace());
                self::assertStringNotContainsString($key, $dump);
                self::assertStringNotContainsString($pan, $dump);
            }
        } finally {
            ini_set('zend.exception_ignore_args', false === $previous ? '1' : $previous);
        }
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidIds(): iterable
    {
        yield 'empty' => [''];
        yield 'dot' => ['.'];
        yield 'dot dot' => ['..'];
        yield 'slash' => ['a/b'];
        yield 'control character' => ["tr1\n"];
        yield 'query' => ['tr1?x=1'];
        yield 'too long' => [str_repeat('a', 65)];
    }

    /**
     * L2: ids that are not Openpay ids never reach the URL path.
     */
    #[DataProvider('invalidIds')]
    public function testInvalidIdsAreRejectedEverywhere(string $id): void
    {
        $client = new Client(new Credentials('mid', 'sk'), new NeverCalledHttpClient());
        $calls = [
            static fn (): array => $client->charges->get($id),
            static fn (): array => $client->customers->update($id, []),
            static fn (): array => $client->customers->delete($id),
            static fn (): array => $client->charges->refund($id),
            static fn (): ResourceApi => $client->customers->nested($id, 'cards'),
        ];
        foreach ($calls as $call) {
            try {
                $call();
                self::fail('Invalid id accepted: '.json_encode($id));
            } catch (OpenpayException $e) {
                self::assertStringStartsWith('Invalid Openpay id', $e->getMessage());
            }
        }
    }

    public function testValidOpenpayIdsAreAccepted(): void
    {
        $http = new RecordingUrlHttpClient();
        $client = new Client(new Credentials('mzdtln0bmtms6o3kck8f', 'sk'), $http);

        $client->customerCards('a9pvykxz4g5rg0fplze0')->get('kqgykn96i7bcs1wwhvgw');
        $client->customers->nested('cus_1-A', 'bank_accounts')->getList();

        self::assertSame([
            'https://sandbox-api.openpay.mx/v1/mzdtln0bmtms6o3kck8f/customers/a9pvykxz4g5rg0fplze0/cards/kqgykn96i7bcs1wwhvgw',
            'https://sandbox-api.openpay.mx/v1/mzdtln0bmtms6o3kck8f/customers/cus_1-A/bank_accounts',
        ], $http->urls);
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function invalidChildResources(): iterable
    {
        yield 'empty' => [''];
        yield 'traversal' => ['../charges'];
        yield 'nested path' => ['cards/x'];
        yield 'upper case' => ['Cards'];
    }

    #[DataProvider('invalidChildResources')]
    public function testInvalidChildResourceIsRejected(string $child): void
    {
        $client = new Client(new Credentials('mid', 'sk'), new NeverCalledHttpClient());

        $this->expectException(OpenpayException::class);
        $this->expectExceptionMessage('Invalid Openpay child resource name.');
        $client->customers->nested('cus1', $child);
    }

    /**
     * L3: the message is built from sanitized, truncated structured fields; the raw body is capped.
     */
    public function testApiErrorMessageIsBuiltFromSanitizedFields(): void
    {
        $longDescription = "Declined\r\nInjected-Line: x ".str_repeat('é', 300);
        $body = json_encode([
            'category' => 'card',
            'error_code' => 3001,
            'description' => $longDescription,
            'request_id' => "req-1\x07",
            'card_number' => '4'.str_repeat('1', 15),
        ], \JSON_THROW_ON_ERROR);

        $e = OpenpayApiException::fromResponse(402, $body);

        self::assertStringStartsWith('Openpay API error HTTP 402 (error_code 3001, category card): Declined Injected-Line: x ', $e->getMessage());
        self::assertStringEndsWith('… [request_id req-1 ]', $e->getMessage());
        self::assertStringNotContainsString("\n", $e->getMessage());
        self::assertStringNotContainsString('1111111111', $e->getMessage());
        $message = $e->getMessage();
        $start = strpos($message, ': ') + 2;
        $description = substr($message, $start, (int) strrpos($message, ' [request_id') - $start);
        self::assertSame(OpenpayApiException::MAX_MESSAGE_FIELD_LENGTH + 1, preg_match_all('/./su', $description), '200 characters + ellipsis.');
        self::assertSame($longDescription, $e->description, 'Structured fields stay unmodified.');
        self::assertSame($body, $e->responseBody);
    }

    public function testApiErrorMessageVariantsAndBodyCap(): void
    {
        self::assertSame('Openpay API error HTTP 500', OpenpayApiException::fromResponse(500, 'x')->getMessage());
        self::assertSame('Openpay API error HTTP 400 (category request)', OpenpayApiException::fromResponse(400, '{"category":"request"}')->getMessage());
        self::assertSame('Openpay API error HTTP 400 (error_code 1001)', OpenpayApiException::fromResponse(400, '{"error_code":1001}')->getMessage());

        $huge = str_repeat('a', OpenpayApiException::MAX_RESPONSE_BODY_BYTES + 10);
        self::assertSame(OpenpayApiException::MAX_RESPONSE_BODY_BYTES, \strlen(OpenpayApiException::fromResponse(502, $huge)->responseBody));
    }

    /**
     * L3: API-provided strings are capped before they reach the logger.
     */
    public function testLoggedApiStringsAreCapped(): void
    {
        $logger = new RecordingLogger();
        $body = json_encode(['category' => str_repeat('c', 300), 'request_id' => str_repeat('r', 50)], \JSON_THROW_ON_ERROR);
        $client = new Client(new Credentials('mid', 'sk'), new FixedHttpClient(new HttpResponse(400, $body)), $logger);

        try {
            $client->charges->get('ch1');
            self::fail('OpenpayApiException expected');
        } catch (OpenpayApiException) {
        }

        $context = $logger->records[0][2];
        self::assertSame(str_repeat('c', 128).'…', $context['category']);
        self::assertSame(str_repeat('r', 50), $context['request_id']);
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string}>
     */
    public static function invalidCredentials(): iterable
    {
        yield 'merchant id with slash' => ['mid/../x', '127.0.0.1', 'Invalid Openpay merchant id'];
        yield 'merchant id with newline' => ["mid\n", '127.0.0.1', 'Invalid Openpay merchant id'];
        yield 'ip list' => ['mid', '1.2.3.4, 5.6.7.8', 'Invalid public IP'];
        yield 'ip with CRLF' => ['mid', "1.2.3.4\r\nX-Evil: 1", 'Invalid public IP'];
        yield 'empty ip' => ['mid', '', 'Invalid public IP'];
    }

    /**
     * L4: merchant id and public IP are validated.
     */
    #[DataProvider('invalidCredentials')]
    public function testInvalidCredentialsAreRejected(string $merchantId, string $publicIp, string $message): void
    {
        $this->expectException(OpenpayException::class);
        $this->expectExceptionMessage($message);
        new Credentials($merchantId, 'sk', publicIp: $publicIp);
    }

    public function testIpv6PublicIpIsAccepted(): void
    {
        self::assertSame('2001:db8::1', (new Credentials('mid', 'sk', publicIp: '2001:db8::1'))->publicIp());
    }

    /**
     * @param list<array<string, mixed>> $trace
     */
    private function flattenTraceArgs(array $trace): string
    {
        $out = '';
        $walk = static function (mixed $value) use (&$walk, &$out): void {
            if (\is_array($value)) {
                foreach ($value as $item) {
                    $walk($item);
                }
            } elseif (\is_object($value)) {
                $out .= $value::class."\n";
            } elseif (\is_scalar($value)) {
                $out .= $value."\n";
            }
        };
        foreach ($trace as $frame) {
            $walk($frame['args'] ?? []);
        }

        return $out;
    }
}

/**
 * Transport double that must not be reached.
 */
final class NeverCalledHttpClient implements HttpClient
{
    public function request(string $method, string $url, #[\SensitiveParameter] ?string $jsonBody = null, #[\SensitiveParameter] array $headers = []): HttpResponse
    {
        throw new \LogicException('The transport must not be called for invalid input: '.$url);
    }
}

/**
 * Transport double that records URLs and answers 200 {}.
 */
final class RecordingUrlHttpClient implements HttpClient
{
    /** @var list<string> */
    public array $urls = [];

    public function request(string $method, string $url, #[\SensitiveParameter] ?string $jsonBody = null, #[\SensitiveParameter] array $headers = []): HttpResponse
    {
        $this->urls[] = $url;

        return new HttpResponse(200, '{}');
    }
}

/**
 * Transport double that always returns the same response.
 */
final readonly class FixedHttpClient implements HttpClient
{
    public function __construct(private HttpResponse $response)
    {
    }

    public function request(string $method, string $url, #[\SensitiveParameter] ?string $jsonBody = null, #[\SensitiveParameter] array $headers = []): HttpResponse
    {
        return $this->response;
    }
}
