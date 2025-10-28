<?php

namespace Gpos\FilamentJibble\Services\Jibble\Resources;

use Gpos\FilamentJibble\Services\Jibble\JibblePaginatedResponse;
use Gpos\FilamentJibble\Services\Jibble\JibbleRequestBuilder;
use Gpos\FilamentJibble\Services\Jibble\JibbleResponse;

interface ResourceContract
{
    /**
     * Retrieve a paginated list for the resource.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $options
     */
    public function list(array $query = [], array $options = []): JibblePaginatedResponse;

    /**
     * Retrieve the entire collection without pagination.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $options
     */
    public function all(array $query = [], array $options = []): JibbleResponse;

    /**
     * Retrieve a specific item by identifier.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $options
     */
    public function find(string $id, array $query = [], array $options = []): JibbleResponse;

    /**
     * Create a new resource entry.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $options
     */
    public function create(array $payload, array $options = []): JibbleResponse;

    /**
     * Update an existing resource entry.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $options
     */
    public function update(string $id, array $payload, array $options = []): JibbleResponse;

    /**
     * Delete a resource entry.
     *
     * @param  array<string, mixed>  $options
     */
    public function delete(string $id, array $options = []): JibbleResponse;

    /**
     * Create a fluent builder for ad-hoc endpoint interactions.
     *
     * @param  array<string, string>  $replacements
     */
    public function builder(array $replacements = []): JibbleRequestBuilder;
}
