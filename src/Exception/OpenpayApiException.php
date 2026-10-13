<?php

declare(strict_types=1);

namespace Nowo\Openpay\Exception;

/**
 * Error returned by the Openpay REST API (HTTP status >= 400).
 *
 * Openpay error bodies look like
 * `{"category":"request","error_code":1001,"description":"...","http_code":400,"request_id":"..."}`;
 * the known fields are exposed as properties (null when absent or not JSON).
 * `getCode()` keeps returning the HTTP status code.
 */
final class OpenpayApiException extends OpenpayException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus,
        public readonly ?int $errorCode = null,
        public readonly ?string $category = null,
        public readonly ?string $description = null,
        public readonly ?string $requestId = null,
        public readonly string $responseBody = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }

    /**
     * Builds the exception from an HTTP error response.
     */
    public static function fromResponse(int $httpStatus, string $body): self
    {
        $decoded = json_decode($body, true);
        $data = \is_array($decoded) ? $decoded : [];

        $errorCode = $data['error_code'] ?? null;
        if (\is_string($errorCode) && ctype_digit($errorCode)) {
            $errorCode = (int) $errorCode;
        }

        return new self(
            \sprintf('Openpay API error HTTP %d: %s', $httpStatus, $body),
            $httpStatus,
            \is_int($errorCode) ? $errorCode : null,
            self::stringOrNull($data['category'] ?? null),
            self::stringOrNull($data['description'] ?? null),
            self::stringOrNull($data['request_id'] ?? null),
            $body,
        );
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return \is_string($value) && '' !== $value ? $value : null;
    }
}
