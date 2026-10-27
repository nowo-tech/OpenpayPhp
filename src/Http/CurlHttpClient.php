<?php

declare(strict_types=1);

namespace Nowo\Openpay\Http;

use Nowo\Openpay\Exception\OpenpayException;

/**
 * cURL transport with connect/request timeouts (REQ-RUNTIME-001).
 */
final readonly class CurlHttpClient implements HttpClient
{
    /** cURL transport; connect and total timeouts in seconds (REQ-RUNTIME-001). */
    public function __construct(
        private int $connectTimeoutSeconds = 5,
        private int $timeoutSeconds = 30,
    ) {
    }

    /** Sends one JSON request; throws OpenpayException on transport failure or timeout. */
    public function request(
        string $method,
        string $url,
        #[\SensitiveParameter]
        ?string $jsonBody = null,
        #[\SensitiveParameter]
        array $headers = [],
    ): HttpResponse {
        $ch = curl_init($url);
        // @codeCoverageIgnoreStart
        if (false === $ch) {
            throw new OpenpayException('Unable to initialize cURL.');
        }
        // @codeCoverageIgnoreEnd

        $headerLines = ['Content-Type: application/json', 'Accept: application/json'];
        foreach ($headers as $name => $value) {
            // Header injection guard: no CR / LF / NUL in names or values.
            if (1 === preg_match('/[\r\n\0]/', $name.$value)) {
                throw new OpenpayException('Invalid HTTP header: CR, LF and NUL are not allowed.');
            }
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
        foreach (self::securityOptions() as $option => $optionValue) {
            curl_setopt($ch, $option, $optionValue);
        }
        if (null !== $jsonBody) {
            curl_setopt($ch, \CURLOPT_POSTFIELDS, $jsonBody);
        }

        $raw = curl_exec($ch);
        if (!\is_string($raw)) {
            throw new OpenpayException('Openpay HTTP request failed: '.curl_error($ch));
        }

        $status = curl_getinfo($ch, \CURLINFO_RESPONSE_CODE);
        $headerSize = curl_getinfo($ch, \CURLINFO_HEADER_SIZE);
        $headerBlob = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);

        return new HttpResponse($status, $body, $this->parseHeaders($headerBlob));
    }

    /**
     * TLS and redirect options set explicitly on every handle (not left to libcurl / php.ini defaults).
     *
     * @return array<int, int|bool>
     */
    public static function securityOptions(): array
    {
        return [
            \CURLOPT_SSL_VERIFYPEER => true,
            \CURLOPT_SSL_VERIFYHOST => 2,
            \CURLOPT_FOLLOWLOCATION => false,
            \CURLOPT_PROTOCOLS => \CURLPROTO_HTTPS,
        ];
    }

    /** @return array<string, list<string>> */
    private function parseHeaders(string $headerBlob): array
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
