<?php

declare(strict_types=1);

namespace Nowo\Openpay\Http;

/**
 * HTTP transport used by the SDK; implement it to plug in another client (e.g. a Symfony HttpClient adapter).
 */
interface HttpClient
{
    /**
     * @param array<string, string> $headers
     */
    public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse;
}
