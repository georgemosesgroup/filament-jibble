<?php

namespace Gpos\FilamentJibble\Services\Jibble;

use Gpos\FilamentJibble\Services\Jibble\Exceptions\JibbleException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;

class JibbleTokenManager
{
    private bool $usingPersonalToken = false;

    public function __construct(
        private readonly HttpFactory $http,
        private readonly CacheRepository $cache,
        private readonly array $config = [],
    ) {
    }

    /**
     * Resolve an access token either from configuration or by issuing a client credentials request.
     */
    public function getToken(): string
    {
        $configuredToken = Arr::get($this->config, 'api_token');

        if (! blank($configuredToken)) {
            $this->usingPersonalToken = true;

            return $configuredToken;
        }

        $clientId = Arr::get($this->config, 'client_id');
        $clientSecret = Arr::get($this->config, 'client_secret');

        if (blank($clientId) || blank($clientSecret)) {
            throw JibbleException::missingClientCredentials();
        }

        $cacheKey = Arr::get($this->config, 'oauth.cache_key', 'jibble.api.access_token');

        $cachedToken = $this->cache->get($cacheKey);

        if (is_string($cachedToken) && $cachedToken !== '') {
            $this->usingPersonalToken = false;

            return $cachedToken;
        }

        $tokenResponse = $this->requestToken($clientId, $clientSecret);

        $ttl = $this->determineTtl((int) Arr::get($tokenResponse, 'expires_in'));
        $this->cache->put($cacheKey, $tokenResponse['access_token'], $ttl);

        $this->usingPersonalToken = false;

        return $tokenResponse['access_token'];
    }

    public function usingPersonalToken(): bool
    {
        return $this->usingPersonalToken;
    }

    private function tokenEndpoint(): string
    {
        $baseUrl = rtrim((string) Arr::get($this->config, 'base_url', ''), '/');
        $endpoint = trim((string) Arr::get($this->config, 'oauth.token_endpoint', 'connect/token'), '/');

        if (str_starts_with($endpoint, 'http://') || str_starts_with($endpoint, 'https://')) {
            return $endpoint;
        }

        return implode('/', array_filter([$baseUrl, $endpoint], fn (?string $segment) => $segment !== ''));
    }

    /**
     * @return array{access_token: string, token_type?: string, expires_in?: int}
     */
    private function requestToken(string $clientId, string $clientSecret): array
    {
        $payload = [
            'grant_type' => Arr::get($this->config, 'oauth.grant_type', 'client_credentials'),
        ];

        $scope = Arr::get($this->config, 'oauth.scope');

        if (! blank($scope)) {
            $payload['scope'] = $scope;
        }

        $response = $this->http
            ->asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->timeout((float) Arr::get($this->config, 'http.timeout', 10))
            ->post($this->tokenEndpoint(), $payload);

        if ($response->failed()) {
            throw JibbleException::tokenRequestFailed($response->json('error') ?? $response->body());
        }

        $responseData = $response->json();

        if (! is_array($responseData) || empty($responseData['access_token'])) {
            throw JibbleException::tokenRequestFailed('Empty access token received from Jibble.');
        }

        return $responseData;
    }

    private function determineTtl(int $expiresIn = 0): int
    {
        $buffer = (int) Arr::get($this->config, 'oauth.cache_ttl_buffer', 60);

        if ($expiresIn <= 0) {
            return Arr::get($this->config, 'oauth.default_cache_ttl', 3600);
        }

        return max($expiresIn - $buffer, $buffer);
    }
}
