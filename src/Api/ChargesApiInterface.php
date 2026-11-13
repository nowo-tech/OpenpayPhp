<?php

declare(strict_types=1);

namespace Nowo\Openpay\Api;

use Nowo\Openpay\Exception\OpenpayApiException;
use Nowo\Openpay\Exception\OpenpayException;
use Nowo\Openpay\Exception\OpenpayInvalidRequestException;

/**
 * Charges operations a service needs (REQ-ARCH-001 rule 1).
 *
 * Type-hint this interface where only charges are used, so the dependency can be replaced or faked in tests.
 * `Client::$charges` and `Client::customerCharges()` return a {@see ResourceApi}, which implements it.
 * Payloads and results are plain arrays (decoded JSON); no framework type appears in the contract.
 */
interface ChargesApiInterface
{
    /**
     * Creates a charge.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     *
     * @throws OpenpayApiException when the API answers with HTTP >= 400
     * @throws OpenpayException    on transport or response errors
     */
    public function create(#[\SensitiveParameter] array $payload): array;

    /**
     * Reads one charge.
     *
     * @return array<string, mixed>
     *
     * @throws OpenpayInvalidRequestException when the id does not look like an Openpay id
     * @throws OpenpayApiException            when the API answers with HTTP >= 400
     */
    public function get(string $id): array;

    /**
     * Lists charges.
     *
     * @param array<string, scalar|null> $query
     *
     * @return array<string, mixed>
     */
    public function getList(array $query = []): array;

    /**
     * Refunds a charge (fully, or partially when the payload carries an amount).
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     *
     * @throws OpenpayInvalidRequestException when the id is invalid
     * @throws OpenpayApiException            when the API answers with HTTP >= 400
     */
    public function refund(string $chargeId, #[\SensitiveParameter] array $payload = []): array;
}
