<?php

namespace Gpos\FilamentJibble\Filament\Pages;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Support\JibbleConnectionFactory;
use Gpos\FilamentJibble\Support\TenantHelper;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class TenantJibbleSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = null;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $title = null;

    protected string $view = 'filament-jibble::filament.pages.tenant-jibble-settings';

    public ?JibbleConnection $connection = null;
    public array $data = [];
    public array $organizationOptions = [];

    public function mount(): void
    {
        abort_unless($this->tenant(), 404);

        $this->connection = $this->resolveConnection();
        $this->form->fill($this->connection?->toArray() ?? []);
        $selected = $this->connection?->organization_uuid ?? Arr::get($this->form->getState(), 'organization_uuid');
        $this->syncOrganizationOptions($selected);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('filament-jibble::resources.pages.tenant.form.sections.connection') ?: 'Connection')
                    ->description(__('filament-jibble::resources.pages.tenant.form.sections.connection_desc') ?: '')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament-jibble::resources.pages.tenant.form.fields.name'))
                            ->default('primary')
                            ->required()
                            ->helperText(__('filament-jibble::resources.pages.tenant.form.helpers.name') ?: 'Short ID for this connection (e.g. "primary").')
                            ->live(debounce: 400),

                        Forms\Components\Select::make('organization_uuid')
                            ->label(__('filament-jibble::resources.pages.tenant.form.fields.organization'))
                            ->options(fn (): array => $this->organizationOptions)
                            ->searchable()
                            ->placeholder(__('filament-jibble::resources.pages.tenant.form.placeholders.organization'))
                            ->helperText(__('filament-jibble::resources.pages.tenant.form.helpers.organization'))
                            ->hintAction(
                                Action::make('fetch-organizations')
                                    ->label(__('filament-jibble::resources.pages.tenant.form.actions.fetch_organizations'))
                                    ->icon('heroicon-o-arrow-path')
                                    ->action('fetchOrganizations')
                            )
                            ->allowHtml(false)
                            ->live(debounce: 400)
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make(__('filament-jibble::resources.pages.tenant.form.sections.credentials') ?: 'Credentials')
                    ->description(__('filament-jibble::resources.pages.tenant.form.sections.credentials_desc') ?: 'Choose either OAuth Client (Client ID/Secret) or a personal API Token.')
                    ->schema([
                        Fieldset::make(__('filament-jibble::resources.pages.tenant.form.sections.oauth') ?: 'OAuth Client')
                            ->schema([
                        Forms\Components\TextInput::make('client_id')
                            ->label(__('filament-jibble::resources.pages.tenant.form.fields.client_id'))
                            ->helperText(__('filament-jibble::resources.pages.tenant.form.helpers.client_id') ?: null)
                            ->nullable()
                            ->live(debounce: 400)
                            ->dehydrated()
                            ->rules(['nullable', 'string']),

                        Forms\Components\TextInput::make('client_secret')
                            ->label(__('filament-jibble::resources.pages.tenant.form.fields.client_secret'))
                            ->password()
                            ->revealable()
                            ->helperText(__('filament-jibble::resources.pages.tenant.form.helpers.client_secret') ?: null)
                            ->nullable()
                            ->live(debounce: 400)
                            ->dehydrated()
                            ->rules(['nullable', 'string']),
                            ])->columnSpanFull()
                            ->columns(2),

                        Forms\Components\Textarea::make('api_token')
                            ->label(__('filament-jibble::resources.pages.tenant.form.fields.api_token'))
                            ->rows(3)
                            ->helperText(__('filament-jibble::resources.pages.tenant.form.helpers.api_token') ?: 'If you provide API Token, Client ID/Secret can be left blank.')
                            ->nullable()
                            ->live(debounce: 400)
                            ->columnSpanFull(),
                    ])->columnSpanFull()
                    ->columns(2),
            ]);
    }

    public function save(JibbleConnectionFactory $factory): void
    {
        $data = $this->form->getState();

        $clientId = blank(Arr::get($data, 'client_id')) ? null : (string) Arr::get($data, 'client_id');
        $clientSecret = blank(Arr::get($data, 'client_secret')) ? null : (string) Arr::get($data, 'client_secret');
        $apiToken = blank(Arr::get($data, 'api_token')) ? null : (string) Arr::get($data, 'api_token');

        if (blank($apiToken) && (blank($clientId) || blank($clientSecret))) {
            throw ValidationException::withMessages([
                'data.client_id' => __('filament-jibble::resources.pages.tenant.validation.credentials'),
                'data.client_secret' => __('filament-jibble::resources.pages.tenant.validation.credentials'),
                'data.api_token' => __('filament-jibble::resources.pages.tenant.validation.credentials'),
            ]);
        }

        $this->form->validate();

        try {
            $connection = $this->connection ?? new JibbleConnection();
            $connection->fill([
                'name' => Arr::get($data, 'name', 'primary'),
                'organization_uuid' => blank(Arr::get($data, 'organization_uuid')) ? null : (string) Arr::get($data, 'organization_uuid'),
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'api_token' => $apiToken,
            ]);

            $connection->tenant_id = $this->tenant()?->getKey();

            if ($connection->organization_uuid) {
                $label = $this->resolveOrganizationLabel($connection, $connection->organization_uuid);
                $connection->organization_name = $label;
                $connection->setSettings('organization_label', $label);
            }

            $connection->save();
            $this->connection = $connection;
            $this->syncOrganizationOptions($connection->organization_uuid);

            Notification::make()
                ->success()
                ->title(__('filament-jibble::resources.pages.tenant.notifications.saved.title'))
                ->send();
        } catch (Throwable $exception) {
            Log::error('Failed to save tenant jibble settings', [
                'message' => $exception->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title(__('filament-jibble::resources.pages.tenant.notifications.save_failed.title'))
                ->body(Str::limit($exception->getMessage(), 200))
                ->send();

            return;
        }

        try {
            $factory->makeManager($this->connection)->client()->request('GET', 'Organizations');

            Notification::make()
                ->success()
                ->title(__('filament-jibble::resources.pages.tenant.notifications.verified.title'))
                ->body(__('filament-jibble::resources.pages.tenant.notifications.verified.body'))
                ->send();

            $this->fetchOrganizations($factory, false);
        } catch (Throwable $exception) {
            Notification::make()
                ->warning()
                ->title(__('filament-jibble::resources.pages.tenant.notifications.verify_failed.title'))
                ->body(Str::limit($exception->getMessage(), 200))
                ->send();
        }
    }

    protected function tenant()
    {
        return TenantHelper::current();
    }

    protected function resolveConnection(): ?JibbleConnection
    {
        $tenant = $this->tenant();

        if (! $tenant) {
            return null;
        }

        return JibbleConnection::query()->firstOrNew([
            'tenant_id' => $tenant->getKey(),
        ], [
            'name' => 'primary',
        ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-jibble::resources.pages.tenant.navigation_label');
    }

    public function getTitle(): string
    {
        return __('filament-jibble::resources.pages.tenant.title');
    }

    public function fetchOrganizations(JibbleConnectionFactory $factory, bool $notify = true): void
    {
        if (! $this->connection?->exists) {
            Notification::make()
                ->warning()
                ->title(__('filament-jibble::resources.pages.tenant.notifications.save_first.title'))
                ->body(__('filament-jibble::resources.pages.tenant.notifications.save_first.body'))
                ->send();

            return;
        }

        try {
            $response = $factory->makeManager($this->connection)
                ->resource('organizations')
                ->all(options: [
                    'organization_uuid' => null,
                ]);

            $organizations = $response->data();

            if (empty($organizations)) {
                Notification::make()
                    ->warning()
                    ->title(__('filament-jibble::resources.pages.tenant.notifications.no_organizations.title'))
                    ->body(__('filament-jibble::resources.pages.tenant.notifications.no_organizations.body'))
                    ->send();

                return;
            }

            $this->organizationOptions = $this->mapOrganizationsToOptions($organizations);
            $selected = Arr::get($this->form->getState(), 'organization_uuid')
                ?? $this->connection->organization_uuid
                ?? array_key_first($this->organizationOptions);

            $this->syncOrganizationOptions($selected);

            if ($notify) {
                Notification::make()
                    ->success()
                    ->title(__('filament-jibble::resources.pages.tenant.notifications.organizations_loaded.title'))
                    ->body(__('filament-jibble::resources.pages.tenant.notifications.organizations_loaded.body'))
                    ->send();
            }
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title(__('filament-jibble::resources.pages.tenant.notifications.organizations_failed.title'))
                ->body(Str::limit($exception->getMessage(), 200))
                ->send();
        }
    }

    protected function syncOrganizationOptions(?string $selected): void
    {
        if (blank($selected)) {
            return;
        }

        $label = $this->organizationOptions[$selected]
            ?? ($this->connection
                ? $this->resolveOrganizationLabel($this->connection, $selected)
                : $selected);

        $this->organizationOptions[$selected] = $label;

        $state = $this->form->getState();

        if (Arr::get($state, 'organization_uuid') !== $selected) {
            $this->form->fill([
                ...$state,
                'organization_uuid' => $selected,
            ]);
        }
    }

    /**
     * @param  array<int|string, mixed>  $organizations
     * @return array<string, string>
     */
    protected function mapOrganizationsToOptions(array $organizations): array
    {
        $organizations = array_is_list($organizations) ? $organizations : [$organizations];
        $options = [];

        foreach ($organizations as $organization) {
            if (! is_array($organization)) {
                continue;
            }

            $id = Arr::get($organization, 'uuid')
                ?? Arr::get($organization, 'id')
                ?? Arr::get($organization, 'OrganizationUUID')
                ?? Arr::get($organization, 'organization_uuid');

            if (blank($id)) {
                continue;
            }

            $label = Arr::get($organization, 'name')
                ?? Arr::get($organization, 'OrganizationName')
                ?? Arr::get($organization, 'organization_name')
                ?? $id;

            $options[(string) $id] = (string) $label;
        }

        return $options;
    }

    protected function resolveOrganizationLabel(JibbleConnection $connection, string $organizationId): string
    {
        return $this->organizationOptions[$organizationId]
            ?? $connection->organization_name
            ?? Arr::get($connection->settings ?? [], 'organization_label')
            ?? $organizationId;
    }
}
