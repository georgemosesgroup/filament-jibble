<?php

namespace Gpos\FilamentJibble\Services\Jibble;

use Illuminate\Support\Arr;

class JibbleRequestBuilder
{
    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, string>  $replacements
     */
    public function __construct(
        private readonly JibbleClient $client,
        private string $endpoint,
        private array $options = [],
        private array $replacements = [],
    ) {
    }

    public function withOrganization(string $organizationUuid): self
    {
        $this->options['organization_uuid'] = $organizationUuid;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function query(array $query): self
    {
        $this->options['query'] = array_merge($this->options['query'] ?? [], $query);

        return $this;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function payload(?array $payload, string $type = 'json'): self
    {
        $this->options['payload'] = $payload;
        $this->options['payload_type'] = $type;

        return $this;
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function headers(array $headers): self
    {
        $this->options['headers'] = array_merge($this->options['headers'] ?? [], $headers);

        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->options['headers'][$name] = $value;

        return $this;
    }

    public function replace(string $placeholder, string $value): self
    {
        $this->replacements[$placeholder] = $value;

        return $this;
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public function withReplacements(array $replacements): self
    {
        $this->replacements = array_merge($this->replacements, $replacements);

        return $this;
    }

    public function withBaseUrl(?string $baseUrl): self
    {
        if ($baseUrl !== null) {
            $this->options['base_url'] = $baseUrl;
        }

        return $this;
    }

    public function get(): JibbleResponse
    {
        return $this->client->request('GET', $this->resolvedEndpoint(), $this->options);
    }

    public function post(): JibbleResponse
    {
        return $this->client->request('POST', $this->resolvedEndpoint(), $this->options);
    }

    public function put(): JibbleResponse
    {
        return $this->client->request('PUT', $this->resolvedEndpoint(), $this->options);
    }

    public function patch(): JibbleResponse
    {
        return $this->client->request('PATCH', $this->resolvedEndpoint(), $this->options);
    }

    public function delete(): JibbleResponse
    {
        return $this->client->request('DELETE', $this->resolvedEndpoint(), $this->options);
    }

    public function paginate(): JibblePaginatedResponse
    {
        $query = Arr::get($this->options, 'query', []);

        return $this->client->paginate(
            $this->resolvedEndpoint(),
            $query,
            Arr::except($this->options, ['query']),
        );
    }

    private function resolvedEndpoint(): string
    {
        $endpoint = $this->endpoint;

        foreach ($this->replacements as $key => $value) {
            if ($key === 'organization') {
                $this->options['organization_uuid'] ??= $value;
                continue;
            }

            $endpoint = str_replace('{'.$key.'}', $value, $endpoint);
        }

        return $endpoint;
    }
}
