<?php

namespace Gpos\FilamentJibble\Filament;

use Gpos\FilamentJibble\Filament\Pages\JibbleApiExplorer;
use Gpos\FilamentJibble\Filament\Pages\JibbleProfileSettingsPage;
use Gpos\FilamentJibble\Filament\Pages\TenantJibbleSettingsPage;
use Gpos\FilamentJibble\Filament\Resources\JibbleConnectionResource;
use Gpos\FilamentJibble\Filament\Resources\JibbleLocationResource;
use Gpos\FilamentJibble\Filament\Resources\JibblePersonResource;
use Gpos\FilamentJibble\Filament\Resources\JibbleSyncLogResource;
use Gpos\FilamentJibble\Filament\Resources\JibbleTimesheetSummaryResource;
use Gpos\FilamentJibble\Filament\Resources\JibbleTimesheetResource;
use Gpos\FilamentJibble\Filament\Resources\JibbleTimeEntryResource;
use Gpos\FilamentJibble\Filament\Widgets\JibbleSyncStatusWidget;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Actions\Action;
use Filament\Facades\Filament;

class FilamentJibblePlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-jibble';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            JibbleApiExplorer::class,
        ])->widgets([
            JibbleSyncStatusWidget::class,
        ])->resources([
            JibbleConnectionResource::class,
            JibblePersonResource::class,
            JibbleLocationResource::class,
            JibbleTimeEntryResource::class,
            JibbleTimesheetResource::class,
            JibbleTimesheetSummaryResource::class,
            JibbleSyncLogResource::class,
        ]);

        if ($panel->hasTenancy()) {
            $panel->pages([
                TenantJibbleSettingsPage::class,
            ])->userMenuItems([
                Action::make('tenant-jibble-settings')
                    ->label(__('filament-jibble::resources.plugin.menu.tenant_settings'))
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->visible(fn (): bool => Filament::getTenant() !== null)
                    ->url(function () use ($panel): ?string {
                        $tenant = Filament::getTenant();

                        if (! $tenant) {
                            return null;
                        }

                        return TenantJibbleSettingsPage::getUrl(
                            isAbsolute: false,
                            panel: $panel->getId(),
                            tenant: $tenant,
                        );
                    }),
            ]);
        } else {
            $panel->pages([
                JibbleProfileSettingsPage::class,
            ])->userMenuItems([
                Action::make('jibble-profile-settings')
                    ->label(__('filament-jibble::resources.plugin.menu.profile_settings'))
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->url(fn () => JibbleProfileSettingsPage::getUrl(
                        isAbsolute: false,
                        panel: $panel->getId(),
                    )),
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
