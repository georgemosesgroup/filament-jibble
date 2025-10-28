<?php

namespace Gpos\FilamentJibble\Services\Jibble;

use Gpos\FilamentJibble\Services\Jibble\Exceptions\JibbleException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JibbleClient
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $baseUrl,
        private readonly ?string $token = null,
        private readonly ?JibbleTokenManager $tokenManager = null,
        private readonly ?string $pathPrefix = null,
        private ?string $organizationUuid = null,
        private readonly array $httpConfig = [],
        private readonly array $paginationConfig = [],
    ) {
    }

    /**
     * Send an HTTP request to the Jibble API.
     *
     * @param  array<string, mixed>  $options
     */
    public function request(string $method, string $endpoint, array $options = []): JibbleResponse
    {
        $baseUrl = $options['base_url'] ?? $this->baseUrl;
        $pathPrefix = $options['path_prefix'] ?? $this->pathPrefix;

        $request = $this->createRequest(
            headers: Arr::get($options, 'headers', []),
            throw: (bool) Arr::get($options, 'throw', true),
            baseUrl: $baseUrl,
        );

        $payloadType = Arr::get($options, 'payload_type', 'json');
        $payload = Arr::get($options, 'payload');
        $organizationUuid = Arr::get($options, 'organization_uuid');

        $requestOptions = Arr::only($options, ['query', 'form_params', 'multipart', 'body']);

        if ($payload !== null) {
            $requestOptions[$payloadType] = $payload;
        }

        $uri = $this->resolveEndpoint($endpoint, $organizationUuid, $pathPrefix);

        $logContext = [
            'method' => $method,
            'base_url' => $baseUrl,
            'endpoint' => $uri,
            'organization_uuid' => $organizationUuid ?? $this->organizationUuid,
            'payload_type' => $payloadType,
            'query' => Arr::get($requestOptions, 'query'),
        ];

        if ($payload !== null) {
            $logContext['payload'] = $payloadType === 'json' ? $payload : '[payload hidden—'.$payloadType.']';
        }

        Log::debug('Jibble HTTP request', $logContext);

        /** @var HttpResponse $response */
        $response = $request->send($method, $uri, $requestOptions);
        Log::debug('Jibble HTTP response', [
            'method' => $method,
            'endpoint' => $uri,
            'status' => $response->status(),
            'body_preview' => Str::limit($response->body(), 500),
        ]);

        if (Arr::get($options, 'throw', true)) {
            $response->throw();
        }

        return new JibbleResponse($response);
    }

    /**
     * Retrieve a paginated response.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $options
     */
    public function paginate(string $endpoint, array $query = [], array $options = []): JibblePaginatedResponse
    {
        $perPage = Arr::get($query, 'perPage') ?? Arr::get($query, 'per_page');

        $query = array_merge([
            'perPage' => $perPage ?? Arr::get($this->paginationConfig, 'default_per_page', 50),
        ], $query);

        $response = $this->request('GET', $endpoint, array_merge($options, [
            'query' => $query,
        ]));

        return new JibblePaginatedResponse($response);
    }

    /**
     * Create a new client instance bound to another organization UUID.
     */
    public function forOrganization(string $organizationUuid): self
    {
        $clone = clone $this;
        $clone->organizationUuid = $organizationUuid;

        return $clone;
    }

    /**
     * Build the configured HTTP pending request.
     *
     * @param  array<string, string>  $headers
     */
    private function createRequest(array $headers = [], bool $throw = true, ?string $baseUrl = null): PendingRequest
    {
        $targetBaseUrl = $baseUrl ?? $this->baseUrl;

        if (blank($targetBaseUrl)) {
            throw JibbleException::missingBaseUrl();
        }

        $request = $this->http
            ->withHeaders(array_merge($this->defaultHeaders(), $headers))
               ->baseUrl($targetBaseUrl)
               ->timeout((float) Arr::get($this->httpConfig, 'timeout', 10));

        $retryTimes = (int) Arr::get($this->httpConfig, 'retry.times', 0);
        $retrySleep = (int) Arr::get($this->httpConfig, 'retry.sleep', 100);

        if ($retryTimes > 0) {
            $request = $request->retry($retryTimes, $retrySleep, throw: $throw);
        }

        return $request;
    }

    private function resolveEndpoint(string $endpoint, ?string $organizationUuid = null, ?string $pathPrefix = null): string
    {
        if (Str::startsWith($endpoint, ['http://', 'https://'])) {
            return $endpoint;
        }

        $resolvedEndpoint = ltrim($endpoint, '/');

        if (Str::contains($resolvedEndpoint, '{organization}')) {
            $organizationUuid = $organizationUuid ?? $this->organizationUuid;

            if (blank($organizationUuid)) {
                throw JibbleException::missingOrganizationUuid();
            }

            $resolvedEndpoint = str_replace('{organization}', $organizationUuid, $resolvedEndpoint);
        }

        $prefix = $pathPrefix ?? $this->pathPrefix;

        if (! empty($prefix)) {
            $resolvedEndpoint = trim($prefix, '/').'/'.$resolvedEndpoint;
        }

        return $resolvedEndpoint;
    }

    /**
     * @return array<string, string>
     */
    private function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->resolveToken(),
        ];
    }

    private function resolveToken(): string
    {
        if (! blank($this->token)) {
            return $this->token;
        }

        if ($this->tokenManager !== null) {
            return $this->tokenManager->getToken();
        }

        throw JibbleException::missingApiToken();
    }
}
