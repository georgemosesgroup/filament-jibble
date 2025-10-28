<?php

namespace Gpos\FilamentJibble\Providers;

use Gpos\FilamentJibble\Support\JibbleConnectionFactory;
use Gpos\FilamentJibble\Support\JibbleConnectionResolver;
use Gpos\FilamentJibble\Filament\Widgets\TimesheetHeatmap;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
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
}
