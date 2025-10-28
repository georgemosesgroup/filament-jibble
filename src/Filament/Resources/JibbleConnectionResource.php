<?php

namespace Gpos\FilamentJibble\Filament\Resources;

use Gpos\FilamentJibble\Filament\Resources\JibbleConnectionResource\Pages;
use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Jobs\SyncLocationsJob;
use Gpos\FilamentJibble\Jobs\SyncPeopleJob;
use Gpos\FilamentJibble\Jobs\SyncTimeEntriesJob;
use Gpos\FilamentJibble\Jobs\SyncTimesheetsJob;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Gpos\FilamentJibble\Support\TenantHelper;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Bus;
use UnitEnum;

class JibbleConnectionResource extends Resource
{
    protected static ?string $model = JibbleConnection::class;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $navigationLabel = null;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrows-right-left';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament-jibble::resources.connections.form.sections.details.title'))
                    ->columns(2)
                    ->components([
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament-jibble::resources.connections.form.fields.name'))
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('organization_uuid')
                            ->label(__('filament-jibble::resources.connections.form.fields.organization_uuid'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('client_id')
                            ->label(__('filament-jibble::resources.connections.form.fields.client_id'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('client_secret')
                            ->label(__('filament-jibble::resources.connections.form.fields.client_secret'))
                            ->password()
                            ->revealable(),
                        Forms\Components\Textarea::make('api_token')
                            ->label(__('filament-jibble::resources.connections.form.fields.api_token'))
                            ->rows(3),
                    ]),
                Section::make(__('filament-jibble::resources.connections.form.sections.settings.title'))
                    ->components([
                        Forms\Components\KeyValue::make('settings')
                            ->label(__('filament-jibble::resources.connections.form.fields.settings.label'))
                            ->keyLabel(__('filament-jibble::resources.connections.form.fields.settings.key'))
                            ->valueLabel(__('filament-jibble::resources.connections.form.fields.settings.value'))
                            ->addButtonLabel(__('filament-jibble::resources.connections.form.fields.settings.add'))
                            ->nullable(),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        $tenantColumn = TenantHelper::tenantColumn();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-jibble::resources.connections.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('organization_uuid')
                    ->label(__('filament-jibble::resources.connections.table.columns.organization_uuid'))
                    ->copyable(),
                TextColumn::make('created_at')
                    ->label(__('filament-jibble::resources.connections.table.columns.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('filament-jibble::resources.connections.table.columns.updated_at'))
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make($tenantColumn)
                    ->label(__('filament-jibble::resources.connections.table.filters.tenant.label'))
                    ->options(fn () => static::tenantOptions())
                    ->searchable()
                    ->hidden(fn () => empty(static::tenantOptions())),
                Tables\Filters\SelectFilter::make('organization_uuid')
                    ->label(__('filament-jibble::resources.connections.table.filters.organization.label'))
                    ->options(fn () => static::organizationOptions())
                    ->searchable()
                    ->hidden(fn () => empty(static::organizationOptions())),
                Tables\Filters\TernaryFilter::make('has_credentials')
                    ->label(__('filament-jibble::resources.connections.table.filters.credentials.label'))
                    ->placeholder(__('filament-jibble::resources.common.all'))
                    ->trueLabel(__('filament-jibble::resources.connections.table.filters.credentials.with'))
                    ->falseLabel(__('filament-jibble::resources.connections.table.filters.credentials.without'))
                    ->queries(
                        true: fn (Builder $query) => $query->where(fn (Builder $query) => $query
                            ->whereNotNull('api_token')
                            ->orWhereNotNull('client_secret')),
                        false: fn (Builder $query) => $query
                            ->whereNull('api_token')
                            ->whereNull('client_secret'),
                    ),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                EditAction::make(),
                Action::make('sync')
                    ->label(__('filament-jibble::resources.connections.table.actions.sync'))
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(fn (JibbleConnection $record) => static::dispatchSync($record)),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageJibbleConnections::route('/'),
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

    public static function getHeaderActions(): array
    {
        return [
            Action::make('sync_all')
                ->label(__('filament-jibble::resources.connections.table.actions.sync_all'))
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(function () {
                    $query = static::getEloquentQuery();

                    $query->cursor()->each(function (JibbleConnection $connection): void {
                        static::dispatchSync($connection);
                    });

                    Notification::make()
                        ->success()
                        ->title(__('filament-jibble::resources.connections.notifications.sync_started.title'))
                        ->body(__('filament-jibble::resources.connections.notifications.sync_started.all'))
                        ->send();
                }),
        ];
    }

    protected static function dispatchSync(JibbleConnection $connection): void
    {
        $startDate = Carbon::today()->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::today()->format('Y-m-d');

        $jobs = [
            new SyncPeopleJob($connection->id),
            new SyncLocationsJob($connection->id),
            new SyncTimeEntriesJob($connection->id, [
                'Date' => $startDate,
                'EndDate' => $endDate,
            ]),
            new SyncTimesheetsJob($connection->id, [
                'Date' => $startDate,
                'EndDate' => $endDate,
            ]),
        ];

        Bus::chain($jobs)->dispatch();

        Notification::make()
            ->success()
            ->title(__('filament-jibble::resources.connections.notifications.sync_started.title'))
            ->body(__('filament-jibble::resources.connections.notifications.sync_started.single', [
                'name' => $connection->name,
            ]))
            ->send();
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-jibble::resources.connections.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-jibble::resources.navigation.groups.integrations');
    }

    public static function getModelLabel(): string
    {
        return __('filament-jibble::resources.connections.label');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-jibble::resources.connections.plural');
    }

    protected static function tenantOptions(): array
    {
        $tenantModel = config('filament-jibble.tenant_model');

        if (! is_string($tenantModel) || ! class_exists($tenantModel)) {
            return [];
        }

        return JibbleConnection::query()
            ->with('tenant')
            ->get()
            ->filter(fn (JibbleConnection $connection) => $connection->tenant !== null)
            ->unique(fn (JibbleConnection $connection) => $connection->tenant->getKey())
            ->mapWithKeys(fn (JibbleConnection $connection) => [
                $connection->tenant->getKey() => $connection->tenant->name ?? (string) $connection->tenant->getKey(),
            ])
            ->sort()
            ->toArray();
    }

    protected static function organizationOptions(): array
    {
        return JibbleConnection::query()
            ->whereNotNull('organization_uuid')
            ->distinct()
            ->orderBy('organization_uuid')
            ->pluck('organization_uuid', 'organization_uuid')
            ->toArray();
    }
}
