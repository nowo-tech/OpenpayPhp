<?php

declare(strict_types=1);

namespace Nowo\Openpay\Http;

interface HttpClient
{
    /**
     * @param array<string, string> $headers
     */
    public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse;
}
