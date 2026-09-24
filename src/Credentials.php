<?php

declare(strict_types=1);

namespace Nowo\Openpay;

use Nowo\Openpay\Exception\OpenpayException;

/**
 * Merchant credentials (instance-scoped — FrankenPHP / worker safe).
 */
final readonly class Credentials
{
    public function __construct(
        private string $merchantId,
        private string $privateKey,
        private Country $country = Country::Mx,
        private bool $sandbox = true,
        private string $publicIp = '127.0.0.1',
    ) {
        if ('' === $this->merchantId || '' === $this->privateKey) {
            throw new OpenpayException('Merchant id and private key are required.');
        }
    }

    public function merchantId(): string
    {
        return $this->merchantId;
    }

    public function privateKey(): string
    {
        return $this->privateKey;
    }

    public function country(): Country
    {
        return $this->country;
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    public function publicIp(): string
    {
        return $this->publicIp;
    }

    public function baseUrl(): string
    {
        return $this->sandbox
            ? $this->country->sandboxBaseUrl()
            : $this->country->productionBaseUrl();
    }

    public function apiRoot(): string
    {
        return rtrim($this->baseUrl(), '/').'/v1/'.$this->merchantId;
    }
}
