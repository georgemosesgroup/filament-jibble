<?php

namespace Gpos\FilamentJibble\Filament\Resources;

use Gpos\FilamentJibble\Filament\Resources\JibbleTimeEntryResource\Pages;
use Gpos\FilamentJibble\Models\JibbleTimeEntry;
use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibblePerson;
use Gpos\FilamentJibble\Models\JibbleLocation;
use Gpos\FilamentJibble\Support\TenantHelper;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;

class JibbleTimeEntryResource extends Resource
{
    protected static ?string $model = JibbleTimeEntry::class;

    protected static ?string $navigationLabel = null;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('picture_url')->label(__('filament-jibble::resources.time_entries.table.columns.picture'))->circular(),
                TextColumn::make('belongs_to_date')->label(__('filament-jibble::resources.time_entries.table.columns.date'))->sortable()->date(),
                TextColumn::make('type')->label(__('filament-jibble::resources.time_entries.table.columns.type'))->badge()->sortable(),
                TextColumn::make('status')->label(__('filament-jibble::resources.time_entries.table.columns.status'))->badge()->sortable(),
                TextColumn::make('person.full_name')->label(__('filament-jibble::resources.time_entries.table.columns.person'))->sortable()->searchable(),
                TextColumn::make('connection.name')->label(__('filament-jibble::resources.time_entries.table.columns.connection'))->sortable(),
                TextColumn::make('time')->label(__('filament-jibble::resources.time_entries.table.columns.time'))->dateTime()->sortable(),
                TextColumn::make('local_time')->label(__('filament-jibble::resources.time_entries.table.columns.local_time'))->dateTime()->sortable(),
                TextColumn::make('location.name')->label(__('filament-jibble::resources.time_entries.table.columns.location'))->toggleable(),
                TextColumn::make('client_type')->label(__('filament-jibble::resources.time_entries.table.columns.client')),
                TextColumn::make('project_id')->label(__('filament-jibble::resources.time_entries.table.columns.project'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('activity_id')->label(__('filament-jibble::resources.time_entries.table.columns.activity'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('location_id')->label(__('filament-jibble::resources.time_entries.table.columns.location_id'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('note')->limit(40)->toggleable(isToggledHiddenByDefault: true),
                BooleanColumn::make('is_outside_geofence')->label(__('filament-jibble::resources.time_entries.table.columns.outside_geofence'))->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->label(__('filament-jibble::resources.time_entries.table.filters.date_range.label'))
                    ->form([
                        Forms\Components\DatePicker::make('from')->label(__('filament-jibble::resources.common.filters.date_range.from')),
                        Forms\Components\DatePicker::make('until')->label(__('filament-jibble::resources.common.filters.date_range.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, string $date) => $query->whereDate('belongs_to_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, string $date) => $query->whereDate('belongs_to_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $from = $data['from'] ?? null;
                        $until = $data['until'] ?? null;

                        if (! $from && ! $until) {
                            return null;
                        }

                        return match (true) {
                            $from && $until => __('filament-jibble::resources.common.filters.date_range.indicator.range', ['from' => $from, 'until' => $until]),
                            $from => __('filament-jibble::resources.common.filters.date_range.indicator.from', ['from' => $from]),
                            $until => __('filament-jibble::resources.common.filters.date_range.indicator.until', ['until' => $until]),
                        };
                    }),
                Tables\Filters\SelectFilter::make('connection_id')
                    ->label(__('filament-jibble::resources.time_entries.table.filters.connection.label'))
                    ->options(fn () => static::connectionOptions())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('person_id')
                    ->label(__('filament-jibble::resources.time_entries.table.filters.person.label'))
                    ->options(fn () => static::personOptions())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('location_id')
                    ->label(__('filament-jibble::resources.time_entries.table.filters.location.label'))
                    ->options(fn () => static::locationOptions())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament-jibble::resources.time_entries.table.filters.status.label'))
                    ->options(fn () => static::statusOptions())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('filament-jibble::resources.time_entries.table.filters.type.label'))
                    ->options(fn () => static::typeOptions())
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('outside_geofence')
                    ->label(__('filament-jibble::resources.time_entries.table.filters.outside_geofence.label'))
                    ->placeholder(__('filament-jibble::resources.common.all'))
                    ->trueLabel(__('filament-jibble::resources.time_entries.table.filters.outside_geofence.true'))
                    ->falseLabel(__('filament-jibble::resources.time_entries.table.filters.outside_geofence.false'))
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_outside_geofence', true),
                        false: fn (Builder $query) => $query->where('is_outside_geofence', false),
                    ),
                Tables\Filters\TernaryFilter::make('has_picture')
                    ->label(__('filament-jibble::resources.time_entries.table.filters.picture.label'))
                    ->placeholder(__('filament-jibble::resources.common.all'))
                    ->trueLabel(__('filament-jibble::resources.time_entries.table.filters.picture.with'))
                    ->falseLabel(__('filament-jibble::resources.time_entries.table.filters.picture.without'))
                    ->queries(
                        true: fn (Builder $query) => $query->where(fn (Builder $query) => $query
                            ->whereNotNull('picture')
                            ->orWhereNotNull('picture_file_id')),
                        false: fn (Builder $query) => $query->whereNull('picture')->whereNull('picture_file_id'),
                    ),
            ], layout: FiltersLayout::AboveContent)
            ->defaultSort('time', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['person', 'connection', 'location']);
    }

    protected static function connectionOptions(): array
    {
        $tenant = TenantHelper::current();

        if ($tenant && method_exists($tenant, 'jibbleConnections')) {
            return $tenant->jibbleConnections()->orderBy('name')->pluck('name', 'id')->toArray();
        }

        return JibbleConnection::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected static function personOptions(): array
    {
        $tenant = TenantHelper::current();
        $people = JibblePerson::query();
        $tenantColumn = TenantHelper::tenantColumn();

        if ($tenant) {
            $people->where($tenantColumn, $tenant->getKey());
        }

        return $people
            ->orderBy('full_name')
            ->pluck('full_name', 'id')
            ->toArray();
    }

    protected static function locationOptions(): array
    {
        $tenant = TenantHelper::current();
        $locations = JibbleLocation::query();
        $tenantColumn = TenantHelper::tenantColumn();

        if ($tenant) {
            $locations->where($tenantColumn, $tenant->getKey());
        }

        return $locations
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected static function statusOptions(): array
    {
        return JibbleTimeEntry::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status', 'status')
            ->toArray();
    }

    protected static function typeOptions(): array
    {
        return JibbleTimeEntry::query()
            ->select('type')
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type', 'type')
            ->toArray();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJibbleTimeEntries::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-jibble::resources.time_entries.navigation_label');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-jibble::resources.navigation.groups.integrations');
    }

    public static function getModelLabel(): string
    {
        return __('filament-jibble::resources.time_entries.label');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-jibble::resources.time_entries.plural');
    }
}
