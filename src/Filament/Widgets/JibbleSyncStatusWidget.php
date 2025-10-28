<?php

namespace Gpos\FilamentJibble\Filament\Widgets;

use Gpos\FilamentJibble\Models\JibbleSyncLog;
use Gpos\FilamentJibble\Support\TenantHelper;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Str;

class JibbleSyncStatusWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getCards(): array
    {
        $tenant = Filament::getTenant();
        $query = JibbleSyncLog::query()
            ->latest('created_at');

        if ($tenant) {
            $query->where(TenantHelper::tenantColumn(), $tenant->getKey());
        }

        $latest = $query->limit(3)->get();

        if ($latest->isEmpty()) {
            return [
                Stat::make(
                    __('filament-jibble::resources.widgets.sync_status.title'),
                    __('filament-jibble::resources.widgets.sync_status.empty.value'),
                )
                    ->description(__('filament-jibble::resources.widgets.sync_status.empty.description'))
                    ->descriptionIcon('heroicon-o-information-circle'),
            ];
        }

        return $latest->map(function (JibbleSyncLog $log) {
            $statusColor = match ($log->status) {
                'completed' => 'success',
                'failed' => 'danger',
                'running' => 'warning',
                default => 'gray',
            };

            $description = $log->finished_at
                ? __('filament-jibble::resources.widgets.sync_status.description.finished', ['time' => $log->finished_at->diffForHumans()])
                : ($log->started_at
                    ? __('filament-jibble::resources.widgets.sync_status.description.started', ['time' => $log->started_at->diffForHumans()])
                    : __('filament-jibble::resources.widgets.sync_status.description.queued'));

            $statusLabel = __('filament-jibble::resources.widgets.sync_status.status.' . $log->status);

            if ($statusLabel === 'filament-jibble::resources.widgets.sync_status.status.' . $log->status) {
                $statusLabel = __('filament-jibble::resources.widgets.sync_status.status.default');
            }

            return Stat::make(Str::headline($log->resource), $statusLabel)
                ->description($description)
                ->color($statusColor)
                ->extraAttributes(['title' => $log->message]);
        })->all();
    }
}
