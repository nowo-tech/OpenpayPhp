<?php

declare(strict_types=1);

namespace Nowo\Openpay\Http;

/**
 * Raw HTTP response returned by an {@see HttpClient}.
 */
final readonly class HttpResponse
{
    /** Status, body and lower-cased headers of an HTTP response. */
    public function __construct(
        public int $statusCode,
        public string $body,
        /** @var array<string, list<string>> */
        public array $headers = [],
    ) {
    }
}
