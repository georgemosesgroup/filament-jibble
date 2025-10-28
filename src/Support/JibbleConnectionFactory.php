<?php

namespace Gpos\FilamentJibble\Support;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Services\Jibble\JibbleClient;
use Gpos\FilamentJibble\Services\Jibble\JibbleManager;
use Gpos\FilamentJibble\Services\Jibble\JibbleTokenManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;

class JibbleConnectionFactory
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly CacheRepository $cache,
    ) {
    }

    public function makeManager(JibbleConnection $connection): JibbleManager
    {
        $config = $this->sanitizeConfig(config('jibble'));
        $tokenManager = $this->makeTokenManager($connection, $config);

        $client = new JibbleClient(
            $this->http,
            (string) ($config['base_url'] ?? ''),
            $connection->api_token,
            $tokenManager,
            $config['path_prefix'] ?? null,
            $connection->organization_uuid,
            (array) ($config['http'] ?? []),
            (array) ($config['pagination'] ?? []),
        );

        return new JibbleManager($client, $config);
    }

    public function makeTokenManager(JibbleConnection $connection, array $config = []): JibbleTokenManager
    {
        return new JibbleTokenManager(
            $this->http,
            $this->cache,
            $this->buildTokenManagerConfig($connection, $config),
        );
    }

    protected function buildTokenManagerConfig(JibbleConnection $connection, array $config = []): array
    {
        $config = $this->sanitizeConfig($config);
        $oauthConfig = $this->sanitizeConfig($config['oauth'] ?? []);

        return array_merge($config, [
            'api_token' => $connection->api_token,
            'client_id' => $connection->client_id,
            'client_secret' => $connection->client_secret,
            'organization_uuid' => $connection->organization_uuid,
            'oauth' => array_merge(
                $oauthConfig,
                ['cache_key' => 'jibble.api.access_token.'.($connection->id ?? 'default')]
            ),
        ]);
    }

    protected function sanitizeConfig(mixed $config): array
    {
        if (! is_array($config)) {
            return [];
        }

        return $config;
    }
}
