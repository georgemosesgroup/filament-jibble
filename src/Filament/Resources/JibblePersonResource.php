<?php

namespace Gpos\FilamentJibble\Filament\Resources;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Gpos\FilamentJibble\Filament\Resources\JibblePersonResource\Pages;
use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibblePerson;
use Gpos\FilamentJibble\Filament\Concerns\HidesResourceNavigationWhenUnauthorized;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Gpos\FilamentJibble\Support\TenantHelper;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Gpos\FilamentJibble\Filament\Widgets\TimesheetHeatmap;
use UnitEnum;
use Illuminate\Support\Str;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class JibblePersonResource extends Resource
{
    use HidesResourceNavigationWhenUnauthorized;

    protected static ?string $model = JibblePerson::class;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = null;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament-jibble::resources.people.sections.person'))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('full_name')
                                    ->label(__('filament-jibble::resources.people.fields.full_name'))
                                    ->disabled(),
                                TextInput::make('preferred_name')
                                    ->label(__('filament-jibble::resources.people.fields.preferred_name'))
                                    ->disabled(),
                                TextInput::make('email')
                                    ->label(__('filament-jibble::resources.people.fields.email'))
                                    ->disabled(),
                                TextInput::make('phone_number')
                                    ->label(__('filament-jibble::resources.people.fields.phone_number'))
                                    ->disabled(),
                                TextInput::make('country_code')
                                    ->label(__('filament-jibble::resources.people.fields.country_code'))
                                    ->disabled(),
                                TextInput::make('role')
                                    ->label(__('filament-jibble::resources.people.fields.role'))
                                    ->disabled(),
                                TextInput::make('status')
                                    ->label(__('filament-jibble::resources.people.fields.status'))
                                    ->disabled(),
                                TextInput::make('code')
                                    ->label(__('filament-jibble::resources.people.fields.code'))
                                    ->disabled(),
                                TextInput::make('pin_code')
                                    ->label(__('filament-jibble::resources.people.fields.pin_code'))
                                    ->disabled(),
                                Placeholder::make('has_embeddings')
                                    ->label(__('filament-jibble::resources.people.fields.has_embeddings'))
                                    ->content(function (?JibblePerson $record): string {
                                        if (is_null($record?->has_embeddings)) {
                                            return __('filament-jibble::resources.common.placeholders.not_available');
                                        }

                                        return $record->has_embeddings
                                            ? __('filament-jibble::resources.common.booleans.with')
                                            : __('filament-jibble::resources.common.booleans.without');
                                    }),
                                TextInput::make('nfc_token')
                                    ->label(__('filament-jibble::resources.people.fields.nfc_token'))
                                    ->disabled(),
                            ]),
                    ])->columnSpanFull(),
                Section::make(__('filament-jibble::resources.people.sections.employment'))
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('organization_id')
                                    ->label(__('filament-jibble::resources.people.fields.organization_id'))
                                    ->disabled(),
                                TextInput::make('group_id')
                                    ->label(__('filament-jibble::resources.people.fields.group_id'))
                                    ->disabled(),
                                TextInput::make('position_id')
                                    ->label(__('filament-jibble::resources.people.fields.position_id'))
                                    ->disabled(),
                                TextInput::make('employment_type_id')
                                    ->label(__('filament-jibble::resources.people.fields.employment_type_id'))
                                    ->disabled(),
                                TextInput::make('user_id')
                                    ->label(__('filament-jibble::resources.people.fields.user_id'))
                                    ->disabled(),
                                TextInput::make('calendar_id')
                                    ->label(__('filament-jibble::resources.people.fields.calendar_id'))
                                    ->disabled(),
                                TextInput::make('schedule_id')
                                    ->label(__('filament-jibble::resources.people.fields.schedule_id'))
                                    ->disabled(),
                                TextInput::make('pay_period_definition_id')
                                    ->label(__('filament-jibble::resources.people.fields.pay_period_definition_id'))
                                    ->disabled(),
                            ]),
                    ]),
                Section::make(__('filament-jibble::resources.people.sections.activity'))
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('work_start_date')
                                    ->label(__('filament-jibble::resources.people.fields.work_start_date'))
                                    ->content(fn (?JibblePerson $record): string => $record?->work_start_date?->toDateString()
                                        ?? __('filament-jibble::resources.common.placeholders.not_available')),
                                Placeholder::make('join_date')
                                    ->label(__('filament-jibble::resources.people.fields.join_date'))
                                    ->content(fn (?JibblePerson $record): string => $record?->join_date?->toDateTimeString()
                                        ?? __('filament-jibble::resources.common.placeholders.not_available')),
                                Placeholder::make('latest_time_entry_time')
                                    ->label(__('filament-jibble::resources.people.fields.latest_time_entry_time'))
                                    ->content(fn (?JibblePerson $record): string => $record?->latest_time_entry_time?->toDateTimeString()
                                        ?? __('filament-jibble::resources.common.placeholders.not_available')),
                                Placeholder::make('invited_at')
                                    ->label(__('filament-jibble::resources.people.fields.invited_at'))
                                    ->content(fn (?JibblePerson $record): string => $record?->invited_at?->toDateTimeString()
                                        ?? __('filament-jibble::resources.common.placeholders.not_available')),
                                Placeholder::make('removed_at')
                                    ->label(__('filament-jibble::resources.people.fields.removed_at'))
                                    ->content(fn (?JibblePerson $record): string => $record?->removed_at?->toDateTimeString()
                                        ?? __('filament-jibble::resources.common.placeholders.not_available')),
                                Placeholder::make('jibble_created_at')
                                    ->label(__('filament-jibble::resources.people.fields.jibble_created_at'))
                                    ->content(fn (?JibblePerson $record): string => $record?->jibble_created_at?->toDateTimeString()
                                        ?? __('filament-jibble::resources.common.placeholders.not_available')),
                                Placeholder::make('jibble_updated_at')
                                    ->label(__('filament-jibble::resources.people.fields.jibble_updated_at'))
                                    ->content(fn (?JibblePerson $record): string => $record?->jibble_updated_at?->toDateTimeString()
                                        ?? __('filament-jibble::resources.common.placeholders.not_available')),
                            ]),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('filament-jibble::resources.people.table.columns.full_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('filament-jibble::resources.people.table.columns.email'))
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('preferred_name')
                    ->label(__('filament-jibble::resources.people.table.columns.preferred_name'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('role')
                    ->label(__('filament-jibble::resources.people.table.columns.role'))
                    ->toggleable(),
                TextColumn::make('phone_number')
                    ->label(__('filament-jibble::resources.people.table.columns.phone_number'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('connection.name')
                    ->label(__('filament-jibble::resources.people.table.columns.connection'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('filament-jibble::resources.people.table.columns.status'))
                    ->badge()->color(fn ($state) => match (Str::lower($state)) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'removed' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('code')
                    ->label(__('filament-jibble::resources.people.table.columns.code'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('work_start_date')
                    ->label(__('filament-jibble::resources.people.table.columns.work_start_date'))
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('latest_time_entry_time')
                    ->label(__('filament-jibble::resources.people.table.columns.latest_time_entry_time'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('has_embeddings')
                    ->label(__('filament-jibble::resources.people.table.columns.has_embeddings'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('filament-jibble::resources.people.table.columns.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament-jibble::resources.people.table.filters.status.label'))
                    ->options(fn () => JibblePerson::query()->distinct()->pluck('status', 'status')->filter()->toArray()),
                Tables\Filters\SelectFilter::make('connection_id')
                    ->label(__('filament-jibble::resources.people.table.filters.connection.label'))
                    ->options(fn () => static::connectionOptions()),
                Tables\Filters\TernaryFilter::make('has_email')
                    ->label(__('filament-jibble::resources.people.table.filters.email.label'))
                    ->placeholder(__('filament-jibble::resources.common.all'))
                    ->trueLabel(__('filament-jibble::resources.people.table.filters.email.with'))
                    ->falseLabel(__('filament-jibble::resources.people.table.filters.email.without'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('email'),
                        false: fn (Builder $query) => $query->whereNull('email'),
                    ),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                ViewAction::make(),
                DeleteAction::make()
                    ->modalHeading(__('filament-jibble::resources.people.table.actions.delete.heading'))
                    ->modalDescription(__('filament-jibble::resources.people.table.actions.delete.description'))
                    ->modalSubmitActionLabel(__('filament-jibble::resources.people.table.actions.delete.confirm'))
                    ->modalCancelActionLabel(__('filament-jibble::resources.people.table.actions.delete.cancel'))
                    ->color('danger')
                    ->using(fn (JibblePerson $record) => $record->deleteWithData()),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->modalHeading(__('filament-jibble::resources.people.table.actions.delete_bulk.heading'))
                    ->modalDescription(__('filament-jibble::resources.people.table.actions.delete_bulk.description'))
                    ->modalSubmitActionLabel(__('filament-jibble::resources.people.table.actions.delete_bulk.confirm'))
                    ->modalCancelActionLabel(__('filament-jibble::resources.people.table.actions.delete_bulk.cancel'))
                    ->action(function (Collection $records): void {
                        $records->each(function (JibblePerson $person): void {
                            $person->deleteWithData();
                        });
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJibblePeople::route('/'),
            'view' => Pages\ViewJibblePerson::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            TimesheetHeatmap::class,
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
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

        return JibbleConnection::query()->pluck('name', 'id')->toArray();
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-jibble::resources.people.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-jibble::resources.navigation.groups.integrations');
    }

    public static function getModelLabel(): string
    {
        return __('filament-jibble::resources.people.label');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-jibble::resources.people.plural');
    }
    
}
