<?php

namespace Gpos\FilamentJibble\Filament\Resources;

use Gpos\FilamentJibble\Filament\Resources\JibbleLocationResource\Pages;
use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibbleLocation;
use Gpos\FilamentJibble\Support\TenantHelper;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Tables\Enums\FiltersLayout;

class JibbleLocationResource extends Resource
{
    protected static ?string $model = JibbleLocation::class;

    protected static string|UnitEnum|null $navigationGroup = null;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = null;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(12)->schema([
                Tabs::make(__('filament-jibble::resources.locations.tabs.main'))
                    ->tabs([
                        // ——— Вкладка: Основное
                        Tab::make(__('filament-jibble::resources.locations.tabs.details'))
                            ->schema([
                                Section::make(__('filament-jibble::resources.locations.sections.general.title'))
                                    ->description(__('filament-jibble::resources.locations.sections.general.description'))
                                    ->columns(12)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('filament-jibble::resources.locations.fields.name'))->disabled()->columnSpan(6),
                                        Forms\Components\TextInput::make('code')
                                            ->label(__('filament-jibble::resources.locations.fields.code'))->disabled()->columnSpan(3)
                                            ->hintAction(
                                                Action::make('copyCode')
                                                    ->label(__('filament-jibble::resources.common.actions.copy'))
                                                    ->icon('heroicon-m-clipboard')
                                                    ->action(fn ($state) => $state) // UI only; Filament покажет тост
                                            ),
                                        Forms\Components\TextInput::make('status')
                                            ->label(__('filament-jibble::resources.locations.fields.status'))->disabled()->columnSpan(3)
                                            ->helperText(__('filament-jibble::resources.locations.fields.status_hint')),
                                        Forms\Components\Textarea::make('address')
                                            ->label(__('filament-jibble::resources.locations.fields.address'))->autosize()->rows(3)->disabled()
                                            ->columnSpan(12),
                                        Forms\Components\Select::make('connection_id')
                                            ->label(__('filament-jibble::resources.locations.fields.connection'))
                                            ->options(fn () => static::connectionOptions())
                                            ->disabled()
                                            ->columnSpan(6),
                                    ]),
                            ]),

                        // ——— Вкладка: Координаты/Геозона
                        Tab::make(__('filament-jibble::resources.locations.tabs.geo'))
                            ->schema([
                                Grid::make(12)->schema([
                                    Fieldset::make(__('filament-jibble::resources.locations.fieldsets.coordinates'))
                                        ->columns(12)
                                        ->schema([
                                            Forms\Components\TextInput::make('latitude')
                                                ->label(__('filament-jibble::resources.locations.fields.latitude'))->disabled()->columnSpan(6)
                                                ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 6, '.', '') : null),
                                            Forms\Components\TextInput::make('longitude')
                                                ->label(__('filament-jibble::resources.locations.fields.longitude'))->disabled()->columnSpan(6)
                                                ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 6, '.', '') : null),
                                        ])
                                        ->columnSpan(12),

                                    Fieldset::make(__('filament-jibble::resources.locations.fieldsets.geofence'))
                                        ->columns(12)
                                        ->schema([
                                            Forms\Components\TextInput::make('geofence_radius')
                                                ->label(__('filament-jibble::resources.locations.fields.radius'))->suffix('m')->disabled()->columnSpan(6),
                                            Forms\Components\TextInput::make('geofence_units')
                                                ->label(__('filament-jibble::resources.locations.fields.units'))->disabled()->columnSpan(6),
                                        ])
                                        ->columnSpan(12),
                                ]),
                            ]),

                        // ——— Вкладка: Payload (JSON)
                        Tab::make(__('filament-jibble::resources.locations.tabs.payload'))
                            ->schema([
                                Forms\Components\Textarea::make('payload')
                                    ->label(__('filament-jibble::resources.locations.fields.payload'))
                                    ->rows(16)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->extraInputAttributes([
                                        'class' => 'font-mono text-xs',
                                    ])
                                    ->helperText(__('filament-jibble::resources.locations.fields.payload_hint'))
                                    ->formatStateUsing(function ($state) {
                                        if (blank($state)) return $state;
                                        try {
                                            $decoded = is_string($state) ? json_decode($state, true, 512, JSON_THROW_ON_ERROR) : $state;
                                            return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                        } catch (\Throwable) {
                                            return (string) $state; // невалидный JSON показываем как есть
                                        }
                                    }),
                            ]),
                    ])->columnSpan(9)
                    ->persistTabInQueryString(),

                Section::make(__('filament-jibble::resources.locations.sections.meta'))
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label(__('filament-jibble::resources.locations.fields.created_at'))
                            ->content(fn ($record) => optional($record?->created_at)?->toDayDateTimeString() ?? __('filament-jibble::resources.common.placeholders.not_available')),
                        Forms\Components\Placeholder::make('updated_at')
                            ->label(__('filament-jibble::resources.locations.fields.updated_at'))
                            ->content(fn ($record) => optional($record?->updated_at)?->toDayDateTimeString() ?? __('filament-jibble::resources.common.placeholders.not_available')),
                        Forms\Components\Placeholder::make('tenant')
                            ->label(__('filament-jibble::resources.locations.fields.tenant'))
                            ->content(fn ($record) => $record?->tenant?->name ?? __('filament-jibble::resources.common.placeholders.not_available')),
                    ])->columnSpan(3),
            ])->columnSpanFull()->columns(12)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')
                    ->label(__('filament-jibble::resources.locations.table.columns.code'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('connection.name')
                    ->label(__('filament-jibble::resources.locations.table.columns.connection'))
                    ->sortable(),
                TextColumn::make('address')
                    ->label(__('filament-jibble::resources.locations.table.columns.address'))
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('latitude')->label(__('filament-jibble::resources.locations.table.columns.latitude'))
                    ->formatStateUsing(fn ($s) => $s !== null ? number_format((float) $s, 6) : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('longitude')->label(__('filament-jibble::resources.locations.table.columns.longitude'))
                    ->formatStateUsing(fn ($s) => $s !== null ? number_format((float) $s, 6) : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('geofence_radius')->label(__('filament-jibble::resources.locations.table.columns.radius'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label(__('filament-jibble::resources.locations.table.columns.imported'))->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(fn () => JibbleLocation::query()->distinct()->pluck('status', 'status')->filter()->toArray()),
                Tables\Filters\SelectFilter::make('connection_id')
                    ->label(__('filament-jibble::resources.locations.table.filters.connection.label'))
                    ->options(fn () => static::connectionOptions()),
                Tables\Filters\TernaryFilter::make('has_coordinates')
                    ->label(__('filament-jibble::resources.locations.table.filters.coordinates.label'))
                    ->placeholder(__('filament-jibble::resources.common.all'))
                    ->trueLabel(__('filament-jibble::resources.locations.table.filters.coordinates.with'))
                    ->falseLabel(__('filament-jibble::resources.locations.table.filters.coordinates.without'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('latitude')->whereNotNull('longitude'),
                        false: fn (Builder $query) => $query->where(fn (Builder $query) => $query
                            ->whereNull('latitude')
                            ->orWhereNull('longitude')),
                    ),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJibbleLocations::route('/'),
            'view' => Pages\ViewJibbleLocation::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('connection');

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
        return __('filament-jibble::resources.locations.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-jibble::resources.navigation.groups.integrations');
    }

    public static function getModelLabel(): string
    {
        return __('filament-jibble::resources.locations.label');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-jibble::resources.locations.plural');
    }
}
