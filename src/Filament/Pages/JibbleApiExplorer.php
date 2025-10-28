<?php

namespace Gpos\FilamentJibble\Filament\Pages;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibblePerson;
use Gpos\FilamentJibble\Services\Jibble\JibbleManager;
use Gpos\FilamentJibble\Services\Jibble\JibblePaginatedResponse;
use Gpos\FilamentJibble\Services\Jibble\JibbleResponse;
use Gpos\FilamentJibble\Support\JibbleConnectionFactory;
use BackedEnum;
use Carbon\Carbon;
use Gpos\FilamentJibble\Support\TenantHelper;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;
use UnitEnum;

class JibbleApiExplorer extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = null;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament-jibble::filament.pages.jibble-api-explorer';

    public array $formState = [];

    public ?array $result = null;

    public ?array $meta = null;

    public ?array $links = null;

    public ?int $status = null;

    public ?array $headers = null;

    public ?string $error = null;

    public function mount(): void
    {
        $connection = $this->defaultConnection();

        $defaults = [
            'connection_id' => $connection?->id,
            'resource' => array_key_first($this->resourceOptions()) ?? '__custom__',
            'http_method' => 'GET',
            'paginate' => true,
            'organization_uuid' => $connection?->organization_uuid,
        ];

        $this->form->fill($defaults);
        $this->formState = $defaults;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('formState')
            ->schema([
            Fieldset::make(__('filament-jibble::resources.pages.api_explorer.fieldsets.request'))
                ->schema([
                    Forms\Components\Select::make('connection_id')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.connection.label'))
                        ->options(fn (): array => $this->connectionOptions())
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn ($state, callable $set) => $this->syncConnectionDefaults($state, $set))
                        ->helperText(__('filament-jibble::resources.pages.api_explorer.fields.connection.helper')),
                    Forms\Components\Select::make('resource')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.resource'))
                        ->options($this->resourceOptions())
                        ->live()
                        ->required(),
                    Forms\Components\TextInput::make('custom_endpoint')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.custom_endpoint.label'))
                        ->placeholder(__('filament-jibble::resources.pages.api_explorer.fields.custom_endpoint.placeholder'))
                        ->visible(fn (Get $get): bool => $get('resource') === '__custom__'),
                    Forms\Components\Select::make('http_method')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.http_method'))
                        ->options([
                            'GET' => 'GET',
                            'POST' => 'POST',
                            'PUT' => 'PUT',
                            'PATCH' => 'PATCH',
                            'DELETE' => 'DELETE',
                        ])
                        ->live()
                        ->required(),
                    Forms\Components\Toggle::make('paginate')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.paginate'))
                        ->visible(fn (Get $get): bool => $get('http_method') === 'GET')
                        ->default(true),
                    Forms\Components\TextInput::make('identifier')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.identifier.label'))
                        ->helperText(__('filament-jibble::resources.pages.api_explorer.fields.identifier.helper')),
                    Forms\Components\TextInput::make('organization_uuid')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.organization_uuid.label'))
                        ->helperText(__('filament-jibble::resources.pages.api_explorer.fields.organization_uuid.helper')),
                    Forms\Components\KeyValue::make('replacements')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.replacements.label'))
                        ->addButtonLabel(__('filament-jibble::resources.pages.api_explorer.fields.replacements.add'))
                        ->live(debounce: 0)
                        ->nullable(),
                    Forms\Components\KeyValue::make('query')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.query.label'))
                        ->addButtonLabel(__('filament-jibble::resources.pages.api_explorer.fields.query.add'))
                        ->live(debounce: 0)
                        ->nullable(),
                    Forms\Components\Textarea::make('payload')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.payload'))
                        ->rows(6)
                        ->nullable(),
                ])
                ->columns(2),
            Fieldset::make(__('filament-jibble::resources.pages.api_explorer.fieldsets.timesheet_filters'))
                ->visible(fn (Get $get): bool => $get('resource') === 'timesheets')
                ->schema([
                    Forms\Components\DatePicker::make('timesheet_query_date')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.date'))
                        ->native(false)
                        ->displayFormat('Y-m-d')
                        ->default(now()->format('Y-m-d')),
                    Forms\Components\Select::make('timesheet_query_period')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.period'))
                        ->options([
                            'Day' => 'Day',
                            'Week' => 'Week',
                            'Month' => 'Month',
                            'Custom' => 'Custom',
                        ])
                        ->default('Day'),
                ])
                ->columns(2),
            Fieldset::make(__('filament-jibble::resources.pages.api_explorer.fieldsets.timesheet_summary_filters'))
                ->visible(fn (Get $get): bool => $get('resource') === 'timesheets_summary')
                ->schema([
                    Forms\Components\Select::make('timesheet_summary_period')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.period'))
                        ->options([
                            'Day' => 'Day',
                            'Week' => 'Week',
                            'Month' => 'Month',
                            'Custom' => 'Custom',
                        ])
                        ->default('Custom'),
                    Forms\Components\DatePicker::make('timesheet_summary_start_date')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.start_date'))
                        ->native(false)
                        ->displayFormat('Y-m-d')
                        ->default(now()->format('Y-m-d')),
                    Forms\Components\DatePicker::make('timesheet_summary_end_date')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.end_date'))
                        ->native(false)
                        ->displayFormat('Y-m-d')
                        ->default(now()->format('Y-m-d')),
                    Forms\Components\Select::make('timesheet_summary_person_ids')
                        ->label(__('filament-jibble::resources.pages.api_explorer.fields.person_ids'))
                        ->multiple()
                        ->searchable()
                        ->options(fn (Get $get) => $this->timesheetSummaryPersonOptions($get('connection_id'))),
                ])
                ->columns(2),
        ]);
    }

    public function submit(JibbleConnectionFactory $factory, JibbleManager $defaultManager): void
    {
        $this->resetResponse();

        $this->form->validate();
        $data = $this->form->getState();

        [$manager, $connection] = $this->resolveManager($factory, $defaultManager, $data['connection_id'] ?? null);

        if (blank($data['organization_uuid']) && $connection) {
            $data['organization_uuid'] = $connection->organization_uuid;
        }

        try {
            $response = $this->dispatchRequest($manager, $connection, $data);

            if ($response instanceof JibblePaginatedResponse) {
                $this->result = $response->items();
                $this->meta = $response->meta();
                $this->links = $response->links();
                $this->status = $response->toResponse()->status();
                $this->headers = $response->toResponse()->response()->headers();
            } elseif ($response instanceof JibbleResponse) {
                $this->result = $response->json();
                $this->meta = $response->meta();
                $this->links = $response->links();
                $this->status = $response->status();
                $this->headers = $response->response()->headers();
            }
        } catch (Throwable $exception) {
            $this->error = $exception->getMessage();
            Log::warning('Jibble API explorer error', [
                'message' => $exception->getMessage(),
                'trace' => Str::limit($exception->getTraceAsString(), 2000),
            ]);
        }
    }

    private function dispatchRequest(JibbleManager $manager, ?JibbleConnection $connection, array $data): JibblePaginatedResponse|JibbleResponse
    {
        $resourceKey = $data['resource'] ?? null;
        $method = strtoupper($data['http_method'] ?? 'GET');
        $identifier = trim((string) ($data['identifier'] ?? '')) ?: null;
        $organizationUuid = trim((string) ($data['organization_uuid'] ?? '')) ?: null;
        $paginate = (bool) ($data['paginate'] ?? false);

        $replacements = $this->normalizeKeyValue($data['replacements'] ?? []);
        $query = $this->normalizeKeyValue($data['query'] ?? []);

        if ($resourceKey === 'timesheets') {
            $query['Date'] = $data['timesheet_query_date'] ?: Carbon::today()->format('Y-m-d');
            $query['Period'] = $data['timesheet_query_period'] ?: 'Day';
        } elseif ($resourceKey === 'timesheets_summary') {
            $query['Period'] = $data['timesheet_summary_period'] ?? 'Custom';
            $query['Date'] = $data['timesheet_summary_start_date'] ?? '2021-12-13';
            $query['EndDate'] = $data['timesheet_summary_end_date'] ?? '2021-12-19';
            $personIds = array_filter($data['timesheet_summary_person_ids'] ?? []);
            if ($personIds) {
                $query['PersonIds'] = array_values($personIds);
            }
        }

        Log::debug('Jibble explorer dispatch', [
            'resource' => $resourceKey,
            'method' => $method,
            'identifier' => $identifier,
            'connection_id' => $connection?->id,
            'organization_uuid' => $organizationUuid,
            'query' => $query,
        ]);

        $payload = $this->decodePayload($data['payload'] ?? null);

        if ($resourceKey === '__custom__') {
            $endpoint = trim((string) ($data['custom_endpoint'] ?? ''));

            if ($endpoint === '') {
                throw new InvalidArgumentException(__('filament-jibble::resources.pages.api_explorer.errors.missing_endpoint'));
            }

            $builder = $manager->endpoint($endpoint)->withReplacements($replacements);

            $org = $organizationUuid ?: $connection?->organization_uuid;

            if ($org) {
                $builder->withOrganization($org);
            }

            if ($query) {
                $builder->query($query);
            }

            if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
                $builder->payload($payload);
            }

            return match ($method) {
                'GET' => $paginate ? $builder->paginate() : $builder->get(),
                'POST' => $builder->post(),
                'PUT' => $builder->put(),
                'PATCH' => $builder->patch(),
                'DELETE' => $builder->delete(),
                default => throw new InvalidArgumentException(__('filament-jibble::resources.pages.api_explorer.errors.unsupported_method', ['method' => $method])),
            };
        }

        $options = [
            'replacements' => $replacements,
            'query' => $query,
        ];

        $options['organization_uuid'] = $organizationUuid ?: $connection?->organization_uuid;

        return match ($method) {
            'GET' => $identifier
                ? $manager->resource($resourceKey)->find($identifier, $query, $options)
                : ($paginate
                    ? $manager->resource($resourceKey)->list($query, $options)
                    : $manager->resource($resourceKey)->all($query, $options)),
            'POST' => $manager->resource($resourceKey)->create($payload ?? [], $options),
            'PUT', 'PATCH' => $manager->resource($resourceKey)->update(
                id: $identifier ?? throw new InvalidArgumentException(__('filament-jibble::resources.pages.api_explorer.errors.missing_identifier_update')),
                payload: $payload ?? [],
                options: $options,
            ),
            'DELETE' => $manager->resource($resourceKey)->delete(
                id: $identifier ?? throw new InvalidArgumentException(__('filament-jibble::resources.pages.api_explorer.errors.missing_identifier_delete')),
                options: $options,
            ),
            default => throw new InvalidArgumentException(__('filament-jibble::resources.pages.api_explorer.errors.unsupported_method', ['method' => $method])),
        };
    }

    protected function resetResponse(): void
    {
        $this->result = null;
        $this->meta = null;
        $this->links = null;
        $this->status = null;
        $this->headers = null;
        $this->error = null;
    }

    private function normalizeKeyValue(array $items): array
    {
        return Collection::make($items)
            ->mapWithKeys(function ($value, $key) {
                if (is_array($value) && array_key_exists('key', $value) && array_key_exists('value', $value)) {
                    $key = $value['key'];
                    $value = $value['value'];
                }

                if (is_string($key)) {
                    $key = trim($key);
                }

                if (is_string($value)) {
                    $value = trim($value);
                }

                return [$key => $value];
            })
            ->filter(fn ($value, $key) => $key !== '' && $value !== null && $value !== '')
            ->toArray();
    }

    private function decodePayload(?string $payload): ?array
    {
        if ($payload === null || trim($payload) === '') {
            return null;
        }

        $decoded = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(__('filament-jibble::resources.pages.api_explorer.errors.invalid_json', [
                'error' => json_last_error_msg(),
            ]));
        }

        if (! is_array($decoded)) {
            throw new InvalidArgumentException(__('filament-jibble::resources.pages.api_explorer.errors.payload_not_object'));
        }

        return $decoded;
    }

    private function connectionOptions(): array
    {
        $tenant = TenantHelper::current();

        if ($tenant && method_exists($tenant, 'jibbleConnections')) {
            return $tenant->jibbleConnections()->pluck('name', 'id')->toArray();
        }

        return JibbleConnection::query()->pluck('name', 'id')->toArray();
    }

    private function resourceOptions(): array
    {
        $configured = array_keys(config('jibble.endpoints', []));

        $options = [];

        foreach ($configured as $resource) {
            $options[$resource] = Str::of($resource)->replace('_', ' ')->headline();
        }

        $options['__custom__'] = __('filament-jibble::resources.pages.api_explorer.resources.custom');

        return $options;
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-jibble::resources.pages.api_explorer.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-jibble::resources.navigation.groups.integrations');
    }

    private function defaultConnection(): ?JibbleConnection
    {
        $tenant = TenantHelper::current();

        if ($tenant && method_exists($tenant, 'jibbleConnections')) {
            return $tenant->jibbleConnections()->first();
        }

        return JibbleConnection::query()->first();
    }

    private function syncConnectionDefaults(?string $connectionId, callable $set): void
    {
        $connection = $this->findConnection($connectionId);
        $set('organization_uuid', $connection?->organization_uuid);
    }

    private function findConnection(?string $connectionId): ?JibbleConnection
    {
        if (! $connectionId) {
            return $this->defaultConnection();
        }

        return JibbleConnection::query()->find($connectionId);
    }

    private function resolveManager(JibbleConnectionFactory $factory, JibbleManager $defaultManager, ?string $connectionId): array
    {
        $connection = $this->findConnection($connectionId);

        if ($connection) {
            return [$factory->makeManager($connection), $connection];
        }

        return [$defaultManager, null];
    }

    private function timesheetSummaryPersonOptions(?string $connectionId): array
    {
        $connection = $this->findConnection($connectionId);

        if (! $connection) {
            return JibblePerson::query()->orderBy('full_name')->pluck('full_name', 'jibble_id')->toArray();
        }

        return JibblePerson::query()
            ->where('connection_id', $connection->id)
            ->orderBy('full_name')
            ->pluck('full_name', 'jibble_id')
            ->toArray();
    }
}
