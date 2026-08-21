<?php

declare(strict_types=1);

namespace Nowo\Openpay\Http;

use Nowo\Openpay\Exception\OpenpayException;

/**
 * cURL transport with connect/request timeouts (REQ-RUNTIME-001).
 */
final class CurlHttpClient implements HttpClient
{
    public function __construct(
        private readonly int $connectTimeoutSeconds = 5,
        private readonly int $timeoutSeconds = 30,
    ) {
    }

    public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse
    {
        $ch = curl_init($url);
        // @codeCoverageIgnoreStart
        if (false === $ch) {
            throw new OpenpayException('Unable to initialize cURL.');
        }
        // @codeCoverageIgnoreEnd

        $headerLines = ['Content-Type: application/json', 'Accept: application/json'];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name.': '.$value;
        }

        $methodUpper = strtoupper($method);
        // @codeCoverageIgnoreStart
        if ('' === $methodUpper) {
            $methodUpper = 'GET';
        }
        // @codeCoverageIgnoreEnd

        curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, $methodUpper);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_HEADER, true);
        curl_setopt($ch, \CURLOPT_HTTPHEADER, $headerLines);
        curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, $this->connectTimeoutSeconds);
        curl_setopt($ch, \CURLOPT_TIMEOUT, $this->timeoutSeconds);
        if (null !== $jsonBody) {
            curl_setopt($ch, \CURLOPT_POSTFIELDS, $jsonBody);
        }

        $raw = curl_exec($ch);
        // @codeCoverageIgnoreStart
        if (!\is_string($raw)) {
            throw new OpenpayException('Openpay HTTP request failed: '.curl_error($ch));
        }
        // @codeCoverageIgnoreEnd

        $status = (int) curl_getinfo($ch, \CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($ch, \CURLINFO_HEADER_SIZE);
        $headerBlob = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);

        return new HttpResponse($status, $body, self::parseHeaders($headerBlob));
    }

    /** @return array<string, list<string>> */
    private static function parseHeaders(string $headerBlob): array
    {
        $headers = [];
        foreach (explode("\r\n", $headerBlob) as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))][] = trim($value);
        }

        return $headers;
    }
}
