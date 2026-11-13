<?php

declare(strict_types=1);

namespace Nowo\Openpay\Tests\Unit;

use Nowo\Openpay\Api\ChargesApiInterface;
use Nowo\Openpay\Client;
use Nowo\Openpay\Credentials;
use Nowo\Openpay\Exception\OpenpayConfigurationException;
use Nowo\Openpay\Exception\OpenpayException;
use Nowo\Openpay\Exception\OpenpayInvalidRequestException;
use Nowo\Openpay\Exception\OpenpayInvalidResponseException;
use Nowo\Openpay\Exception\OpenpayTransportException;
use Nowo\Openpay\Http\HttpClient;
use Nowo\Openpay\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

final class TypedExceptionsAndChargesInterfaceTest extends TestCase
{
    public function testChargesResourcesImplementTheChargesInterface(): void
    {
        $client = new Client(new Credentials('mid', 'sk'), $this->http(200, '{"id":"ch1"}'));

        self::assertInstanceOf(ChargesApiInterface::class, $client->charges);
        self::assertInstanceOf(ChargesApiInterface::class, $client->customerCharges('cus1'));
        self::assertSame('ch1', $this->charge($client->charges)['id']);
    }

    public function testChargesInterfaceCanBeFaked(): void
    {
        $fake = new class implements ChargesApiInterface {
            public function create(array $payload): array
            {
                return ['id' => 'fake', 'amount' => $payload['amount']];
            }

            public function get(string $id): array
            {
                return ['id' => $id];
            }

            public function getList(array $query = []): array
            {
                return [];
            }

            public function refund(string $chargeId, array $payload = []): array
            {
                return ['id' => $chargeId, 'status' => 'refunded'];
            }
        };

        self::assertSame('fake', $fake->create(['amount' => 5])['id']);
    }

    public function testInvalidIdIsInvalidRequestException(): void
    {
        $client = new Client(new Credentials('mid', 'sk'), $this->http(200, '{}'));

        $this->expectException(OpenpayInvalidRequestException::class);
        $client->charges->get('../x');
    }

    public function testRefundOnOtherResourceIsInvalidRequestException(): void
    {
        $client = new Client(new Credentials('mid', 'sk'), $this->http(200, '{}'));

        $this->expectException(OpenpayInvalidRequestException::class);
        $client->customers->refund('abc');
    }

    public function testInvalidCredentialsAreConfigurationExceptions(): void
    {
        $this->expectException(OpenpayConfigurationException::class);
        new Credentials('', 'sk');
    }

    public function testInvalidIpIsConfigurationException(): void
    {
        $this->expectException(OpenpayConfigurationException::class);
        new Credentials('mid', 'sk', publicIp: 'not-an-ip');
    }

    public function testInvalidJsonIsInvalidResponseException(): void
    {
        $client = new Client(new Credentials('mid', 'sk'), $this->http(200, 'not json'));

        $this->expectException(OpenpayInvalidResponseException::class);
        $client->charges->get('ch1');
    }

    public function testTransportFailureKeepsTheBaseType(): void
    {
        $e = new OpenpayTransportException('x');

        self::assertInstanceOf(OpenpayException::class, $e);
        self::assertInstanceOf(OpenpayException::class, new OpenpayConfigurationException('x'));
        self::assertInstanceOf(OpenpayException::class, new OpenpayInvalidRequestException('x'));
        self::assertInstanceOf(OpenpayException::class, new OpenpayInvalidResponseException('x'));
    }

    /**
     * @return array<string, mixed>
     */
    private function charge(ChargesApiInterface $charges): array
    {
        return $charges->get('ch1');
    }

    private function http(int $status, string $body): HttpClient
    {
        return new readonly class($status, $body) implements HttpClient {
            public function __construct(private int $status, private string $body)
            {
            }

            public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse
            {
                return new HttpResponse($this->status, $this->body);
            }
        };
    }
}
