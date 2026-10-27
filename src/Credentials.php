<?php

declare(strict_types=1);

namespace Nowo\Openpay;

use Nowo\Openpay\Exception\OpenpayException;

/**
 * Merchant credentials (instance-scoped — FrankenPHP / worker safe).
 */
final readonly class Credentials
{
    /**
     * Validates and holds the merchant credentials; the private key is a sensitive parameter (redacted in stack traces).
     *
     * @param string $publicIp end-user IP (Openpay anti-fraud); behind a proxy take it from
     *                         `Request::getClientIp()` with `trusted_proxies` configured, never from a raw header
     *
     * @throws OpenpayException when a value is empty, the merchant id is not alphanumeric or the IP is invalid
     */
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
        // The merchant id is part of every URL path: Openpay merchant ids are alphanumeric.
        if (1 !== preg_match('/^[A-Za-z0-9]+$/D', $this->merchantId)) {
            throw new OpenpayException('Invalid Openpay merchant id: expected letters and digits only.');
        }
        // Sent as X-Forwarded-For: must be a single IPv4/IPv6 address (no lists, no CR/LF).
        if (false === filter_var($this->publicIp, \FILTER_VALIDATE_IP)) {
            throw new OpenpayException('Invalid public IP: expected a single IPv4 or IPv6 address.');
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
