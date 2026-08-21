<?php

declare(strict_types=1);

namespace Nowo\Openpay\Api;

use Nowo\Openpay\Client;
use Nowo\Openpay\Exception\OpenpayException;

/**
 * Thin REST resource helper (path relative to merchant root).
 */
final class ResourceApi
{
    public function __construct(
        private readonly Client $client,
        private readonly string $resourcePath,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        return $this->client->request('POST', $this->resourcePath, $payload);
    }

    /** @return array<string, mixed> */
    public function get(string $id): array
    {
        return $this->client->request('GET', $this->resourcePath.'/'.rawurlencode($id));
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
    public function update(string $id, array $payload): array
    {
        return $this->client->request('PUT', $this->resourcePath.'/'.rawurlencode($id), $payload);
    }

    /** @return array<string, mixed> */
    public function delete(string $id): array
    {
        return $this->client->request('DELETE', $this->resourcePath.'/'.rawurlencode($id));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function refund(string $chargeId, array $payload = []): array
    {
        if (!str_contains($this->resourcePath, 'charges')) {
            throw new OpenpayException('refund() is only available on charges resources.');
        }

        return $this->client->request(
            'POST',
            $this->resourcePath.'/'.rawurlencode($chargeId).'/refund',
            $payload
        );
    }

    public function nested(string $parentId, string $childResource): self
    {
        return new self(
            $this->client,
            $this->resourcePath.'/'.rawurlencode($parentId).'/'.$childResource
        );
    }
}
