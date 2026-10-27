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
 *
 * The message is built only from the structured fields (sanitized and truncated), never from the raw body.
 * `$responseBody` is **untrusted** data from the network (capped at 64 KiB): log or inspect it, never render it.
 */
final class OpenpayApiException extends OpenpayException
{
    /** Maximum bytes of the raw error body kept in {@see $responseBody}. */
    public const int MAX_RESPONSE_BODY_BYTES = 65536;

    /** Maximum characters of a field (description, category, request id) copied into the message. */
    public const int MAX_MESSAGE_FIELD_LENGTH = 200;

    /** Error returned by the Openpay API, with the parsed error fields. */
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

        $errorCode = \is_int($errorCode) ? $errorCode : null;
        $category = self::stringOrNull($data['category'] ?? null);
        $description = self::stringOrNull($data['description'] ?? null);
        $requestId = self::stringOrNull($data['request_id'] ?? null);

        $message = \sprintf('Openpay API error HTTP %d', $httpStatus);
        if (null !== $errorCode) {
            $message .= \sprintf(' (error_code %d', $errorCode).(null !== $category ? ', category '.self::forMessage($category) : '').')';
        } elseif (null !== $category) {
            $message .= ' (category '.self::forMessage($category).')';
        }
        if (null !== $description) {
            $message .= ': '.self::forMessage($description);
        }
        if (null !== $requestId) {
            $message .= ' [request_id '.self::forMessage($requestId).']';
        }

        return new self(
            $message,
            $httpStatus,
            $errorCode,
            $category,
            $description,
            $requestId,
            substr($body, 0, self::MAX_RESPONSE_BODY_BYTES),
        );
    }

    /** Strips control characters and truncates a field before it is copied into the exception message. */
    private static function forMessage(string $value): string
    {
        // Values come from json_decode(), so they are valid UTF-8 and the /u patterns cannot fail.
        $clean = (string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value);
        if (1 === preg_match('/^(.{'.self::MAX_MESSAGE_FIELD_LENGTH.'}).+/su', $clean, $m)) {
            return $m[1].'…';
        }

        return $clean;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return \is_string($value) && '' !== $value ? $value : null;
    }
}
