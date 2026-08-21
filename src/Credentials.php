<?php

declare(strict_types=1);

namespace Nowo\Openpay;

use Nowo\Openpay\Exception\OpenpayException;

/**
 * Merchant credentials (instance-scoped — FrankenPHP / worker safe).
 */
final class Credentials
{
    public function __construct(
        private readonly string $merchantId,
        private readonly string $privateKey,
        private readonly Country $country = Country::Mx,
        private readonly bool $sandbox = true,
        private readonly string $publicIp = '127.0.0.1',
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
