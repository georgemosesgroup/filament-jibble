<?php

namespace Gpos\FilamentJibble\Filament\Resources;

use Gpos\FilamentJibble\Filament\Resources\JibbleTimesheetSummaryResource\Pages;
use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibblePerson;
use Gpos\FilamentJibble\Models\JibbleTimesheetSummary;
use Gpos\FilamentJibble\Support\TenantHelper;
use Carbon\CarbonInterval;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Tables\Enums\FiltersLayout;

class JibbleTimesheetSummaryResource extends Resource
{
    protected static ?string $model = JibbleTimesheetSummary::class;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = null;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('person.full_name')
                    ->label(__('filament-jibble::resources.timesheet_summaries.table.columns.person'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('connection.name')
                    ->label(__('filament-jibble::resources.timesheet_summaries.table.columns.connection'))
                    ->sortable(),
                TextColumn::make('period')
                    ->label(__('filament-jibble::resources.timesheet_summaries.table.columns.period'))
                    ->state(fn (JibbleTimesheetSummary $record) => static::formatPeriod($record))
                    ->toggleable(),
                TextColumn::make('tracked_seconds')
                    ->label(__('filament-jibble::resources.timesheet_summaries.table.columns.tracked'))
                    ->formatStateUsing(fn ($state, JibbleTimesheetSummary $record) => static::formatHours(static::resolveTrackedSeconds($record))),
                TextColumn::make('payroll_seconds')
                    ->label(__('filament-jibble::resources.timesheet_summaries.table.columns.billable'))
                    ->toggleable()
                    ->formatStateUsing(fn ($state, JibbleTimesheetSummary $record) => static::formatHours(static::resolveBillableSeconds($record))),
                TextColumn::make('break_seconds')
                    ->label(__('filament-jibble::resources.timesheet_summaries.table.columns.breaks'))
                    ->state(fn (JibbleTimesheetSummary $record) => static::resolveBreakSeconds($record))
                    ->formatStateUsing(fn ($state) => static::formatHours($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('filament-jibble::resources.timesheet_summaries.table.columns.updated'))
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->label(__('filament-jibble::resources.timesheet_summaries.table.filters.date_range.label'))
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
                Tables\Filters\SelectFilter::make('connection_id')
                    ->label(__('filament-jibble::resources.timesheet_summaries.table.filters.connection.label'))
                    ->options(fn () => static::connectionOptions()),
                Tables\Filters\SelectFilter::make('person_id')
                    ->label(__('filament-jibble::resources.timesheet_summaries.table.filters.person.label'))
                    ->searchable()
                    ->options(fn () => static::personOptions()),
            ], layout: FiltersLayout::AboveContent)
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJibbleTimesheetSummaries::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['person', 'connection'])
            ->orderByDesc('date');

        $tenant = TenantHelper::current();
        $tenantColumn = TenantHelper::tenantColumn();

        if ($tenant) {
            $query->where($tenantColumn, $tenant->getKey());
        }

        return $query;
    }

    protected static function formatHours(mixed $seconds): string
    {
        $seconds = (int) $seconds;

        if ($seconds <= 0) {
            return '0 h';
        }

        return number_format($seconds / 3600, 2).' h';
    }

    protected static function resolveTrackedSeconds(JibbleTimesheetSummary $record): int
    {
        $seconds = (int) $record->tracked_seconds;

        if ($seconds > 0) {
            return $seconds;
        }

        $summary = static::summaryArray($record);

        $duration = data_get($summary, 'tracked')
            ?? data_get($summary, 'total');

        return static::convertDurationToSeconds($duration);
    }

    protected static function resolveBillableSeconds(JibbleTimesheetSummary $record): int
    {
        $seconds = (int) $record->payroll_seconds;

        if ($seconds > 0) {
            return $seconds;
        }

        $summary = static::summaryArray($record);

        $duration = data_get($summary, 'payroll')
            ?? data_get($summary, 'regular')
            ?? data_get($summary, 'total');

        return static::convertDurationToSeconds($duration);
    }

    protected static function resolveBreakSeconds(JibbleTimesheetSummary $record): int
    {
        $summary = static::summaryArray($record);

        $explicit = data_get($summary, 'break_seconds');

        if (is_numeric($explicit)) {
            return (int) $explicit;
        }

        $duration = data_get($summary, 'breakTime')
            ?? data_get($summary, 'totalBreakTime');

        return static::convertDurationToSeconds($duration);
    }

    protected static function formatPeriod(JibbleTimesheetSummary $record): string
    {
        $start = data_get($record->summary, 'start_date') ?? $record->date?->toDateString();
        $end = data_get($record->summary, 'end_date') ?? $record->date?->toDateString();

        if ($start === $end) {
            return $start ?? (string) $record->period;
        }

        if ($start && $end) {
            return $start.' → '.$end;
        }

        return (string) $record->period;
    }

    protected static function connectionOptions(): array
    {
        $tenant = TenantHelper::current();

        if ($tenant && method_exists($tenant, 'jibbleConnections')) {
            return $tenant->jibbleConnections()->pluck('name', 'id')->toArray();
        }

        return JibbleConnection::query()->pluck('name', 'id')->toArray();
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

    protected static function convertDurationToSeconds(mixed $duration): int
    {
        if (! is_string($duration) || trim($duration) === '') {
            return 0;
        }

        try {
            return (int) ceil(CarbonInterval::make($duration)->totalSeconds);
        } catch (\Throwable) {
            return 0;
        }
    }

    protected static function summaryArray(JibbleTimesheetSummary $record): array
    {
        $summary = $record->summary;

        if (is_array($summary)) {
            return $summary;
        }

        if (is_string($summary)) {
            $decoded = json_decode($summary, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-jibble::resources.timesheet_summaries.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-jibble::resources.navigation.groups.integrations');
    }

    public static function getModelLabel(): string
    {
        return __('filament-jibble::resources.timesheet_summaries.label');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-jibble::resources.timesheet_summaries.plural');
    }
}
