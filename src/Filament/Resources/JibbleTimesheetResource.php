<?php

namespace Gpos\FilamentJibble\Filament\Resources;

use Gpos\FilamentJibble\Filament\Resources\JibbleTimesheetResource\Pages;
use Gpos\FilamentJibble\Models\JibbleTimesheet;
use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibblePerson;
use Gpos\FilamentJibble\Support\TenantHelper;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Tables\Enums\FiltersLayout;

class JibbleTimesheetResource extends Resource
{
    protected static ?string $model = JibbleTimesheet::class;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = null;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label(__('filament-jibble::resources.timesheets.table.columns.date'))->sortable()->date(),
                TextColumn::make('status')->label(__('filament-jibble::resources.timesheets.table.columns.status'))->badge()->sortable(),
                TextColumn::make('person.full_name')->label(__('filament-jibble::resources.timesheets.table.columns.person'))->searchable()->sortable(),
                TextColumn::make('connection.name')->label(__('filament-jibble::resources.timesheets.table.columns.connection'))->sortable(),
                TextColumn::make('tracked_seconds')->label(__('filament-jibble::resources.timesheets.table.columns.tracked_seconds'))->sortable()->formatStateUsing(fn ($state) => number_format((int) $state)),
                TextColumn::make('billable_seconds')->label(__('filament-jibble::resources.timesheets.table.columns.billable_seconds'))->sortable()->formatStateUsing(fn ($state) => number_format((int) $state))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('break_seconds')->label(__('filament-jibble::resources.timesheets.table.columns.break_seconds'))->sortable()->formatStateUsing(fn ($state) => number_format((int) $state))->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->label(__('filament-jibble::resources.timesheets.table.filters.date_range.label'))
                    ->form([
                        Forms\Components\DatePicker::make('from')->label(__('filament-jibble::resources.common.filters.date_range.from')),
                        Forms\Components\DatePicker::make('until')->label(__('filament-jibble::resources.common.filters.date_range.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, string $date) => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, string $date) => $query->whereDate('date', '<=', $date),
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
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament-jibble::resources.timesheets.table.filters.status.label'))
                    ->options(fn () => static::statusOptions())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('connection_id')
                    ->label(__('filament-jibble::resources.timesheets.table.filters.connection.label'))
                    ->options(fn () => static::connectionOptions())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('person_id')
                    ->label(__('filament-jibble::resources.timesheets.table.filters.person.label'))
                    ->options(fn () => static::personOptions())
                    ->searchable(),
            ], layout: FiltersLayout::AboveContent)
            ->defaultSort('date', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['person', 'connection']);
    }

    protected static function statusOptions(): array
    {
        return JibbleTimesheet::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status', 'status')
            ->toArray();
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJibbleTimesheets::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-jibble::resources.timesheets.navigation_label');
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
        return __('filament-jibble::resources.timesheets.label');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-jibble::resources.timesheets.plural');
    }
}
