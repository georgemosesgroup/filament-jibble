<?php

namespace Gpos\FilamentJibble\Services\Jibble\Resources;

use Gpos\FilamentJibble\Services\Jibble\Exceptions\JibbleException;
use Gpos\FilamentJibble\Services\Jibble\JibbleClient;
use Gpos\FilamentJibble\Services\Jibble\JibblePaginatedResponse;
use Gpos\FilamentJibble\Services\Jibble\JibbleRequestBuilder;
use Gpos\FilamentJibble\Services\Jibble\JibbleResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class GenericResource implements ResourceContract
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected readonly JibbleClient $client,
        protected readonly array $config = [],
    ) {
    }

    public function list(array $query = [], array $options = []): JibblePaginatedResponse
    {
        [$query, $options] = $this->prepareRequest($query, $options);

        $endpoint = $this->buildPath($this->config['path'] ?? null, $options);

        Log::debug('Jibble resource list', [
            'resource' => $this->name(),
            'endpoint' => $endpoint,
            'query' => $query,
            'options' => $this->filterOptions($options),
        ]);

        return $this->client->paginate($endpoint, $query, $this->filterOptions($options));
    }

    public function all(array $query = [], array $options = []): JibbleResponse
    {
        [$query, $options] = $this->prepareRequest($query, $options);

        $endpoint = $this->buildPath($this->config['path'] ?? null, $options);

        Log::debug('Jibble resource all', [
            'resource' => $this->name(),
            'endpoint' => $endpoint,
            'query' => $query,
            'options' => $this->filterOptions($options),
        ]);

        return $this->client->request('GET', $endpoint, array_merge(
            $this->filterOptions($options),
            ['query' => $query],
        ));
    }

    public function find(string $id, array $query = [], array $options = []): JibbleResponse
    {
        [$query, $options] = $this->prepareRequest($query, $options);

        $endpoint = $this->buildDetailPath($id, $options);

        return $this->client->request('GET', $endpoint, array_merge(
            $this->filterOptions($options),
            ['query' => $query],
        ));
    }

    public function create(array $payload, array $options = []): JibbleResponse
    {
        [, $options] = $this->prepareRequest([], $options);

        $endpoint = $this->buildPath($this->config['path'] ?? null, $options);

        return $this->client->request('POST', $endpoint, array_merge(
            $this->filterOptions($options),
            [
                'payload' => $payload,
                'payload_type' => Arr::get($options, 'payload_type', 'json'),
            ],
        ));
    }

    public function update(string $id, array $payload, array $options = []): JibbleResponse
    {
        [, $options] = $this->prepareRequest([], $options);

        $endpoint = $this->buildDetailPath($id, $options);

        return $this->client->request('PUT', $endpoint, array_merge(
            $this->filterOptions($options),
            [
                'payload' => $payload,
                'payload_type' => Arr::get($options, 'payload_type', 'json'),
            ],
        ));
    }

    public function delete(string $id, array $options = []): JibbleResponse
    {
        [, $options] = $this->prepareRequest([], $options);

        $endpoint = $this->buildDetailPath($id, $options);

        return $this->client->request('DELETE', $endpoint, $this->filterOptions($options));
    }

    public function builder(array $replacements = []): JibbleRequestBuilder
    {
        $endpoint = $this->buildPath($this->config['path'] ?? null, ['replacements' => $replacements], false);

        return (new JibbleRequestBuilder($this->client, $endpoint))
            ->withReplacements($replacements)
            ->withBaseUrl($this->resolveBaseUrl());
    }

    public function config(): array
    {
        return $this->config;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $options
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    protected function prepareRequest(array $query, array $options = []): array
    {
        $defaults = $this->config['defaults'] ?? [];

        $options['base_url'] ??= $this->resolveBaseUrl();
        $options['path_prefix'] ??= $this->config['path_prefix'] ?? null;

        Log::debug('Jibble resource prepareRequest (input)', [
            'resource' => $this->name(),
            'query' => $query,
            'options' => $options,
            'defaults' => $defaults,
        ]);

        $options['headers'] = array_merge($defaults['headers'] ?? [], $options['headers'] ?? []);
        $options['replacements'] = array_merge($defaults['replacements'] ?? [], $options['replacements'] ?? []);

        if (($this->config['organization_scoped'] ?? false)) {
            $options['organization_uuid'] ??= config('jibble.organization_uuid');

            if (blank($options['organization_uuid'])) {
                throw JibbleException::missingOrganizationUuid();
            }

            $options['replacements']['organization_uuid'] ??= $options['organization_uuid'];
        }

        $query = array_merge($defaults['query'] ?? [], $query, $options['query'] ?? []);
        unset($options['query']);

        if (isset($options['organization_uuid'])) {
            $query = array_map(fn ($value) => is_string($value) ? str_replace('{organization_uuid}', $options['organization_uuid'], $value) : $value, $query);
            $options['replacements'] = array_map(fn ($value) => is_string($value) ? str_replace('{organization_uuid}', $options['organization_uuid'], $value) : $value, $options['replacements']);
        }

        Log::debug('Jibble resource prepareRequest (normalized)', [
            'resource' => $this->name(),
            'query' => $query,
            'options' => $options,
        ]);

        return [$query, $options];
    }

    private function resolveBaseUrl(): ?string
    {
        $service = $this->config['service'] ?? null;

        if ($service === null) {
            return null;
        }

        return Arr::get(config('jibble.services'), $service);
    }

    protected function buildDetailPath(string $id, array $options = []): string
    {
        $replacements = $options['replacements'] ?? [];
        $replacements[$this->idPlaceholder()] = $id;

        $detailPath = $this->config['detail_path'] ?? $this->config['path'] ?? null;

        if ($detailPath === null) {
            throw new InvalidArgumentException(sprintf('Jibble resource [%s] detail path is not configured.', $this->name()));
        }

        if (! str_contains($detailPath, '{'.$this->idPlaceholder().'}')) {
            $detailPath = rtrim($detailPath, '/').'/'.'{'.$this->idPlaceholder().'}';
        }

        return $this->buildPath($detailPath, array_merge($options, ['replacements' => $replacements]));
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function buildPath(?string $path, array $options = [], bool $validate = true): string
    {
        if ($path === null) {
            throw new InvalidArgumentException(sprintf('Jibble resource [%s] path is not configured.', $this->name()));
        }

        $path = trim($path, '/');

        $replacements = array_merge(
            $this->config['defaults'] ?? [],
            Arr::get($options, 'replacements', []),
        );

        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $path, $matches);
        $placeholders = $matches[1] ?? [];

        foreach ($placeholders as $placeholder) {
            if (in_array($placeholder, ['organization', 'organization_uuid'], true)) {
                continue;
            }

            if (! array_key_exists($placeholder, $replacements)) {
                if ($validate) {
                    throw new InvalidArgumentException(sprintf(
                        'Missing replacement for placeholder [%s] on Jibble resource [%s].',
                        $placeholder,
                        $this->name(),
                    ));
                }

                continue;
            }

            $path = str_replace('{'.$placeholder.'}', (string) $replacements[$placeholder], $path);
        }

        return $path;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function filterOptions(array $options): array
    {
        return Arr::except($options, ['replacements', 'payload_type']);
    }

    protected function idPlaceholder(): string
    {
        return $this->config['id_placeholder'] ?? 'id';
    }

    protected function name(): string
    {
        return (string) ($this->config['name'] ?? 'resource');
    }
}
