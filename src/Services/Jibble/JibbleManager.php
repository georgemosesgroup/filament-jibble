<?php

namespace Gpos\FilamentJibble\Services\Jibble;

use Gpos\FilamentJibble\Services\Jibble\Resources\GenericResource;
use Gpos\FilamentJibble\Services\Jibble\Resources\ResourceContract;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class JibbleManager
{
    /**
     * @var array<string, ResourceContract>
     */
    private array $resources = [];

    public function __construct(
        private readonly JibbleClient $client,
        private readonly array $config = [],
    ) {
    }

    public function client(): JibbleClient
    {
        return $this->client;
    }

    /**
     * Resolve a configured resource by name.
     */
    public function resource(string $name): ResourceContract
    {
        if (isset($this->resources[$name])) {
            return $this->resources[$name];
        }

        $resourceConfig = Arr::get($this->config, "endpoints.{$name}");

        if ($resourceConfig === null) {
            throw new InvalidArgumentException("Jibble resource [{$name}] is not configured.");
        }

        $resourceClass = Arr::get($resourceConfig, 'resource', GenericResource::class);

        /** @var ResourceContract $resource */
        $resource = new $resourceClass($this->client, array_merge($resourceConfig, ['name' => $name]));

        return $this->resources[$name] = $resource;
    }

    /**
     * Create a fluent builder for an arbitrary endpoint.
     */
    public function endpoint(string $endpoint): JibbleRequestBuilder
    {
        return new JibbleRequestBuilder($this->client, $endpoint);
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->resource($name);
    }
}
