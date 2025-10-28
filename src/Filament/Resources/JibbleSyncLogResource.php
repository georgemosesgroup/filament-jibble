<?php

namespace Gpos\FilamentJibble\Filament\Resources;

use Gpos\FilamentJibble\Filament\Resources\JibbleSyncLogResource\Pages;
use Gpos\FilamentJibble\Models\JibbleSyncLog;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Gpos\FilamentJibble\Support\TenantHelper;
use Gpos\FilamentJibble\Models\JibbleConnection;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Tables\Enums\FiltersLayout;

class JibbleSyncLogResource extends Resource
{
    protected static ?string $model = JibbleSyncLog::class;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = null;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label(__('filament-jibble::resources.sync_logs.table.columns.queued'))->dateTime()->sortable(),
                TextColumn::make('resource')->label(__('filament-jibble::resources.sync_logs.table.columns.resource'))->badge()->sortable(),
                TextColumn::make('status')->label(__('filament-jibble::resources.sync_logs.table.columns.status'))->badge()->color(fn ($state) => match ($state) {
                    'running' => 'warning',
                    'failed' => 'danger',
                    'completed' => 'success',
                    default => 'gray',
                }),
                TextColumn::make('connection.name')->label(__('filament-jibble::resources.sync_logs.table.columns.connection')),
                TextColumn::make('message')->label(__('filament-jibble::resources.sync_logs.table.columns.message'))->wrap()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('started_at')->dateTime()->label(__('filament-jibble::resources.sync_logs.table.columns.started_at')),
                TextColumn::make('finished_at')->dateTime()->label(__('filament-jibble::resources.sync_logs.table.columns.finished_at')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament-jibble::resources.sync_logs.table.filters.status.label'))
                    ->options([
                        'running' => __('filament-jibble::resources.sync_logs.table.filters.status.options.running'),
                        'failed' => __('filament-jibble::resources.sync_logs.table.filters.status.options.failed'),
                        'completed' => __('filament-jibble::resources.sync_logs.table.filters.status.options.completed'),
                    ]),
                Tables\Filters\SelectFilter::make('resource')
                    ->label(__('filament-jibble::resources.sync_logs.table.filters.resource.label'))
                    ->options([
                        'people' => __('filament-jibble::resources.sync_logs.table.filters.resource.options.people'),
                        'timesheets' => __('filament-jibble::resources.sync_logs.table.filters.resource.options.timesheets'),
                        'timesheets_summary' => __('filament-jibble::resources.sync_logs.table.filters.resource.options.timesheets_summary'),
                        'time_entries' => __('filament-jibble::resources.sync_logs.table.filters.resource.options.time_entries'),
                    ]),
                Tables\Filters\SelectFilter::make('connection_id')
                    ->label(__('filament-jibble::resources.sync_logs.table.filters.connection.label'))
                    ->options(fn () => static::connectionOptions())
                    ->searchable(),
                Tables\Filters\Filter::make('queued_between')
                    ->label(__('filament-jibble::resources.sync_logs.table.filters.queued_between.label'))
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('filament-jibble::resources.common.filters.date_range.from')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('filament-jibble::resources.common.filters.date_range.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->actions([])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJibbleSyncLogs::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $tenant = TenantHelper::current();
        $tenantColumn = TenantHelper::tenantColumn();

        if ($tenant) {
            $query->where($tenantColumn, $tenant->getKey());
        }

        return $query;
    }

    protected static function connectionOptions(): array
    {
        $tenant = TenantHelper::current();

        if ($tenant && method_exists($tenant, 'jibbleConnections')) {
            return $tenant->jibbleConnections()->pluck('name', 'id')->toArray();
        }

        return JibbleConnection::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-jibble::resources.sync_logs.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-jibble::resources.navigation.groups.integrations');
    }

    public static function getModelLabel(): string
    {
        return __('filament-jibble::resources.sync_logs.label');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-jibble::resources.sync_logs.plural');
    }
}
