<?php

namespace Gpos\FilamentJibble\Filament\Resources;

use Gpos\FilamentJibble\Filament\Resources\JibblePersonResource\Pages;
use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibblePerson;
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
use Filament\Tables\Table;
use Gpos\FilamentJibble\Filament\Widgets\TimesheetHeatmap;
use UnitEnum;
use Illuminate\Support\Str;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class JibblePersonResource extends Resource
{
    protected static ?string $model = JibblePerson::class;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = null;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('full_name')->disabled(),
                Forms\Components\TextInput::make('email')->disabled(),
                Forms\Components\Textarea::make('payload')->disabled()->rows(12),
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
        return __('filament-jibble::resources.people.label');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-jibble::resources.people.plural');
    }
}
