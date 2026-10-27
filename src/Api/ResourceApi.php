<?php

declare(strict_types=1);

namespace Nowo\Openpay\Api;

use Nowo\Openpay\Client;
use Nowo\Openpay\Exception\OpenpayException;

/**
 * Thin REST resource helper (path relative to merchant root).
 */
final readonly class ResourceApi
{
    /** Openpay object ids are short alphanumeric strings (e.g. `trzjaozcik8msyqshka4`); `_` / `-` allowed for safety margin. */
    private const string ID_PATTERN = '/^[A-Za-z0-9_-]{1,64}$/D';

    /** Child resource names are lower-case path segments (`charges`, `cards`, `bank_accounts`, …). */
    private const string RESOURCE_PATTERN = '/^[a-z_]+$/D';

    /** API for one Openpay resource path (e.g. charges, customers/{id}/cards), bound to a client. */
    public function __construct(
        private Client $client,
        private string $resourcePath,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function create(#[\SensitiveParameter] array $payload): array
    {
        return $this->client->request('POST', $this->resourcePath, $payload);
    }

    /**
     * Alias of {@see create()} (matches legacy Openpay SDK `->add()` naming).
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function add(#[\SensitiveParameter] array $payload): array
    {
        return $this->create($payload);
    }

    /** @return array<string, mixed> */
    public function get(string $id): array
    {
        return $this->client->request('GET', $this->resourcePath.'/'.$this->id($id));
    }

    /**
     * @param array<string, scalar|null> $query
     *
     * @return array<string, mixed>
     */
    public function getList(array $query = []): array
    {
        $path = $this->resourcePath;
        if ([] !== $query) {
            $path .= '?'.http_build_query($query);
        }

        return $this->client->request('GET', $path);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function update(string $id, #[\SensitiveParameter] array $payload): array
    {
        return $this->client->request('PUT', $this->resourcePath.'/'.$this->id($id), $payload);
    }

    /** @return array<string, mixed> */
    public function delete(string $id): array
    {
        return $this->client->request('DELETE', $this->resourcePath.'/'.$this->id($id));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function refund(string $chargeId, #[\SensitiveParameter] array $payload = []): array
    {
        // Only "charges" or ".../{id}/charges"; an id that merely contains "charges" must not pass.
        if ('charges' !== $this->resourcePath && !str_ends_with($this->resourcePath, '/charges')) {
            throw new OpenpayException('refund() is only available on charges resources.');
        }

        return $this->client->request(
            'POST',
            $this->resourcePath.'/'.$this->id($chargeId).'/refund',
            $payload
        );
    }

    /** Returns the API of a child resource of the given parent id (e.g. customers/{id}/charges). */
    public function nested(string $parentId, string $childResource): self
    {
        if (1 !== preg_match(self::RESOURCE_PATTERN, $childResource)) {
            throw new OpenpayException('Invalid Openpay child resource name.');
        }

        return new self(
            $this->client,
            $this->resourcePath.'/'.$this->id($parentId).'/'.$childResource
        );
    }

    /**
     * Validates an Openpay object id before it is put in the URL path (no '', '.', '..', '/', control characters).
     *
     * @throws OpenpayException when the id does not look like an Openpay id
     */
    private function id(string $id): string
    {
        if (1 !== preg_match(self::ID_PATTERN, $id)) {
            throw new OpenpayException('Invalid Openpay id: expected 1-64 characters [A-Za-z0-9_-].');
        }

        return $id;
    }
}
