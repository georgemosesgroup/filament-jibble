<?php

namespace Gpos\FilamentJibble\Providers;

use Gpos\FilamentJibble\Services\Jibble\JibbleClient;
use Gpos\FilamentJibble\Services\Jibble\JibbleManager;
use Gpos\FilamentJibble\Services\Jibble\JibbleTokenManager;
use Gpos\FilamentJibble\Support\JibbleConnectionFactory;
use Gpos\FilamentJibble\Support\JibbleConnectionResolver;
use Gpos\FilamentJibble\Filament\Widgets\TimesheetHeatmap;
use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibbleLocation;
use Gpos\FilamentJibble\Models\JibblePerson;
use Gpos\FilamentJibble\Models\JibbleSyncLog;
use Gpos\FilamentJibble\Models\JibbleTimeEntry;
use Gpos\FilamentJibble\Models\JibbleTimesheet;
use Gpos\FilamentJibble\Models\JibbleTimesheetSummary;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class FilamentJibbleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/filament-jibble.php', 'filament-jibble');
        $this->mergeConfigFrom(__DIR__.'/../../config/jibble.php', 'jibble');
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'filament-jibble');
        $this->loadJsonTranslationsFrom(__DIR__.'/../../resources/lang');

        $this->app->singleton(JibbleConnectionFactory::class, function ($app): JibbleConnectionFactory {
            return new JibbleConnectionFactory(
                $app->make(HttpFactory::class),
                $app->make(CacheRepository::class),
            );
        });

        $this->app->singleton(JibbleTokenManager::class, function ($app): JibbleTokenManager {
            $config = $this->jibbleConfig();

            return new JibbleTokenManager(
                $app->make(HttpFactory::class),
                $app->make(CacheRepository::class),
                $config,
            );
        });

        $this->app->singleton(JibbleClient::class, function ($app): JibbleClient {
            $config = $this->jibbleConfig();

            return new JibbleClient(
                http: $app->make(HttpFactory::class),
                baseUrl: (string) Arr::get($config, 'base_url', ''),
                token: Arr::get($config, 'api_token'),
                tokenManager: $app->make(JibbleTokenManager::class),
                pathPrefix: Arr::get($config, 'path_prefix'),
                organizationUuid: Arr::get($config, 'organization_uuid'),
                httpConfig: (array) Arr::get($config, 'http', []),
                paginationConfig: (array) Arr::get($config, 'pagination', []),
            );
        });

        $this->app->singleton(JibbleManager::class, function ($app): JibbleManager {
            $config = $this->jibbleConfig();

            return new JibbleManager(
                client: $app->make(JibbleClient::class),
                config: $config,
            );
        });

        $this->app->singleton(JibbleConnectionResolver::class, fn (): JibbleConnectionResolver => new JibbleConnectionResolver());
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/filament-jibble.php' => config_path('filament-jibble.php'),
        ], 'filament-jibble-config');

        $this->publishes([
            __DIR__.'/../../config/jibble.php' => config_path('jibble.php'),
        ], 'filament-jibble-config');

        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('migrations'),
        ], 'filament-jibble-migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'filament-jibble');

        $this->publishes([
            __DIR__.'/../../resources/lang' => lang_path('vendor/filament-jibble'),
        ], 'filament-jibble-translations');

        $this->registerTenantRelationshipAlias();

        Livewire::component(
            'gpos.filament-jibble.filament.widgets.timesheet-heatmap',
            TimesheetHeatmap::class,
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Gpos\FilamentJibble\Console\Commands\JibbleSyncCommand::class,
            ]);
        }

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedules = config('filament-jibble.sync.schedule', []);

            foreach ($schedules as $resource => $expression) {
                if (! $expression) {
                    continue;
                }

                $schedule->command('jibble:sync --resource='.$resource)->cron($expression);
            }
        });
    }

    protected function registerTenantRelationshipAlias(): void
    {
        $relationship = (string) (config('filament-jibble.tenant_relationship', 'tenant'));

        if ($relationship === 'tenant') {
            return;
        }

        $models = [
            JibbleConnection::class,
            JibbleLocation::class,
            JibblePerson::class,
            JibbleSyncLog::class,
            JibbleTimeEntry::class,
            JibbleTimesheet::class,
            JibbleTimesheetSummary::class,
        ];

        foreach ($models as $model) {
            $model::resolveRelationUsing($relationship, static fn ($model) => $model->tenant());
        }
    }

    private function jibbleConfig(): array
    {
        $config = config('jibble', []);

        return is_array($config) ? $config : [];
    }
}
