<?php

declare(strict_types=1);

namespace Nowo\Openpay;

use Nowo\Openpay\Exception\OpenpayException;

/**
 * Merchant credentials (instance-scoped — FrankenPHP / worker safe).
 */
final readonly class Credentials
{
    /** Validates and holds the merchant credentials; the private key is a sensitive parameter (redacted in stack traces). */
    public function __construct(
        private string $merchantId,
        #[\SensitiveParameter]
        private string $privateKey,
        private Country $country = Country::Mx,
        private bool $sandbox = true,
        private string $publicIp = '127.0.0.1',
    ) {
        if ('' === $this->merchantId || '' === $this->privateKey) {
            throw new OpenpayException('Merchant id and private key are required.');
        }
    }

    /** Openpay merchant id. */
    public function merchantId(): string
    {
        return $this->merchantId;
    }

    /** Private API key used for HTTP Basic authentication; never log it. */
    public function privateKey(): string
    {
        return $this->privateKey;
    }

    /** Country whose Openpay API is used. */
    public function country(): Country
    {
        return $this->country;
    }

    /** Whether requests go to the sandbox API. */
    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    /** Client IP sent to Openpay as X-Forwarded-For (used by its anti-fraud checks). */
    public function publicIp(): string
    {
        return $this->publicIp;
    }

    /** Sandbox or production base URL for the configured country. */
    public function baseUrl(): string
    {
        return $this->sandbox
            ? $this->country->sandboxBaseUrl()
            : $this->country->productionBaseUrl();
    }

    /** Merchant API root ({baseUrl}/v1/{merchantId}). */
    public function apiRoot(): string
    {
        return rtrim($this->baseUrl(), '/').'/v1/'.$this->merchantId;
    }
}
