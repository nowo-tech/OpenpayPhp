<?php

declare(strict_types=1);

namespace Nowo\Openpay\Http;

/**
 * HTTP transport used by the SDK; implement it to plug in another client (e.g. a Symfony HttpClient adapter).
 */
interface HttpClient
{
    /**
     * Implementations must mark `$jsonBody` and `$headers` as `#[\SensitiveParameter]` too: they carry card data and
     * the `Authorization` header (attributes are not inherited).
     *
     * @param array<string, string> $headers
     */
    public function request(
        string $method,
        string $url,
        #[\SensitiveParameter]
        ?string $jsonBody = null,
        #[\SensitiveParameter]
        array $headers = [],
    ): HttpResponse;
}
