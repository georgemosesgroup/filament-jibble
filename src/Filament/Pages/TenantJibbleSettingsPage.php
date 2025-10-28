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
    public array $projectOptions = [];
    public array $groupOptions = [];

    public function mount(): void
    {
        abort_unless($this->tenant(), 404);

        $this->connection = $this->resolveConnection();
        $this->form->fill($this->connection?->toArray() ?? []);
        $selected = $this->connection?->organization_uuid ?? Arr::get($this->form->getState(), 'organization_uuid');
        $this->syncOrganizationOptions($selected);

        $settings = $this->connection?->settings ?? [];

        $defaultProjectId = $this->connection?->getDefaultProjectId();
        $defaultGroupId = $this->connection?->getDefaultGroupId();

        if ($defaultProjectId) {
            $this->projectOptions[$defaultProjectId] = Arr::get($settings, 'default_project_label', $defaultProjectId);
        }

        if ($defaultGroupId) {
            $this->groupOptions[$defaultGroupId] = Arr::get($settings, 'default_group_label', $defaultGroupId);
        }

        $this->form->fill([
            ...$this->form->getState(),
            'default_project_id' => $defaultProjectId,
            'default_group_id' => $defaultGroupId,
        ]);
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

                Section::make(__('filament-jibble::resources.pages.profile.section_title') ?: 'Jibble preferences')
                    ->description(__('filament-jibble::resources.pages.profile.helpers.default_project') ? __('filament-jibble::resources.pages.profile.helpers.default_project') : null)
                    ->schema([
                        Forms\Components\Select::make('default_project_id')
                            ->label(__('filament-jibble::resources.pages.profile.fields.default_project'))
                            ->options(fn (): array => $this->projectOptions)
                            ->placeholder(__('filament-jibble::resources.pages.profile.placeholders.default_project'))
                            ->helperText(__('filament-jibble::resources.pages.profile.helpers.default_project'))
                            ->searchable()
                            ->allowHtml(false)
                            ->live(debounce: 400)
                            ->nullable()
                            ->hintAction(
                                Action::make('fetch-projects')
                                    ->label(__('filament-jibble::resources.pages.profile.actions.fetch_projects'))
                                    ->icon('heroicon-o-arrow-path')
                                    ->action('fetchProjects')
                            ),
                        Forms\Components\Select::make('default_group_id')
                            ->label(__('filament-jibble::resources.pages.profile.fields.default_group'))
                            ->options(fn (): array => $this->groupOptions)
                            ->placeholder(__('filament-jibble::resources.pages.profile.placeholders.default_group'))
                            ->helperText(__('filament-jibble::resources.pages.profile.helpers.default_group'))
                            ->searchable()
                            ->allowHtml(false)
                            ->live(debounce: 400)
                            ->nullable()
                            ->hintAction(
                                Action::make('fetch-groups')
                                    ->label(__('filament-jibble::resources.pages.profile.actions.fetch_groups'))
                                    ->icon('heroicon-o-arrow-path')
                                    ->action('fetchGroups')
                            ),
                    ])
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

            $connection->setTenantKey($this->tenant()?->getKey());

            $projectId = blank(Arr::get($data, 'default_project_id')) ? null : (string) Arr::get($data, 'default_project_id');
            $groupId = blank(Arr::get($data, 'default_group_id')) ? null : (string) Arr::get($data, 'default_group_id');

            $connection->setSettings('default_project_id', $projectId);
            $connection->setSettings(
                'default_project_label',
                $projectId ? ($this->projectOptions[$projectId] ?? $projectId) : null,
            );

            $connection->setSettings('default_group_id', $groupId);
            $connection->setSettings(
                'default_group_label',
                $groupId ? ($this->groupOptions[$groupId] ?? $groupId) : null,
            );

            if ($connection->organization_uuid) {
                $label = $this->resolveOrganizationLabel($connection, $connection->organization_uuid);
                $connection->organization_name = $label;
                $connection->setSettings('organization_label', $label);
            }

            $connection->save();
            $this->connection = $connection;
            $this->syncOrganizationOptions($connection->organization_uuid);

            if ($projectId) {
                $this->projectOptions[$projectId] = $connection->getSettings('default_project_label', $projectId);
            }

            if ($groupId) {
                $this->groupOptions[$groupId] = $connection->getSettings('default_group_label', $groupId);
            }

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

        $tenantColumn = TenantHelper::tenantColumn();

        return JibbleConnection::query()->firstOrNew([
            $tenantColumn => $tenant->getKey(),
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

    public function fetchProjects(JibbleConnectionFactory $factory): void
    {
        if (! $this->ensureConnectionExists()) {
            return;
        }

        $organizationUuid = $this->resolveOrganizationUuid();

        if (blank($organizationUuid)) {
            $this->notifyRequiresOrganization();

            return;
        }

        try {
            $response = $factory->makeManager($this->connection)
                ->resource('projects')
                ->all(options: [
                    'organization_uuid' => $organizationUuid,
                ]);

            $projects = $response->data();

            if (empty($projects)) {
                Notification::make()
                    ->warning()
                    ->title(__('filament-jibble::resources.pages.profile.notifications.no_projects.title'))
                    ->body(__('filament-jibble::resources.pages.profile.notifications.no_projects.body'))
                    ->send();

                return;
            }

            $this->projectOptions = $this->mapProjectsToOptions($projects);

            $state = $this->form->getState();
            $current = Arr::get($state, 'default_project_id');

            if (blank($current) || ! array_key_exists($current, $this->projectOptions)) {
                $state['default_project_id'] = array_key_first($this->projectOptions);
                $this->form->fill($state);
            }

            Notification::make()
                ->success()
                ->title(__('filament-jibble::resources.pages.profile.notifications.projects_loaded.title'))
                ->body(__('filament-jibble::resources.pages.profile.notifications.projects_loaded.body'))
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title(__('filament-jibble::resources.pages.profile.notifications.projects_failed.title'))
                ->body(Str::limit($exception->getMessage(), 200))
                ->send();
        }
    }

    public function fetchGroups(JibbleConnectionFactory $factory): void
    {
        if (! $this->ensureConnectionExists()) {
            return;
        }

        $organizationUuid = $this->resolveOrganizationUuid();

        if (blank($organizationUuid)) {
            $this->notifyRequiresOrganization();

            return;
        }

        try {
            $response = $factory->makeManager($this->connection)
                ->resource('groups')
                ->all(options: [
                    'organization_uuid' => $organizationUuid,
                ]);

            $groups = $response->data();

            if (empty($groups)) {
                Notification::make()
                    ->warning()
                    ->title(__('filament-jibble::resources.pages.profile.notifications.no_groups.title'))
                    ->body(__('filament-jibble::resources.pages.profile.notifications.no_groups.body'))
                    ->send();

                return;
            }

            $this->groupOptions = $this->mapGroupsToOptions($groups);

            $state = $this->form->getState();
            $current = Arr::get($state, 'default_group_id');

            if (blank($current) || ! array_key_exists($current, $this->groupOptions)) {
                $state['default_group_id'] = array_key_first($this->groupOptions);
                $this->form->fill($state);
            }

            Notification::make()
                ->success()
                ->title(__('filament-jibble::resources.pages.profile.notifications.groups_loaded.title'))
                ->body(__('filament-jibble::resources.pages.profile.notifications.groups_loaded.body'))
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title(__('filament-jibble::resources.pages.profile.notifications.groups_failed.title'))
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

    protected function ensureConnectionExists(): bool
    {
        if ($this->connection?->exists) {
            return true;
        }

        Notification::make()
            ->warning()
            ->title(__('filament-jibble::resources.pages.profile.notifications.requires_connection.title'))
            ->body(__('filament-jibble::resources.pages.profile.notifications.requires_connection.body'))
            ->send();

        return false;
    }

    protected function notifyRequiresOrganization(): void
    {
        Notification::make()
            ->warning()
            ->title(__('filament-jibble::resources.pages.profile.notifications.requires_organization.title'))
            ->body(__('filament-jibble::resources.pages.profile.notifications.requires_organization.body'))
            ->send();
    }

    protected function resolveOrganizationUuid(): ?string
    {
        $state = $this->form->getState();

        return Arr::get($state, 'organization_uuid') ?? $this->connection?->organization_uuid;
    }

    /**
     * @param  array<int|string, mixed>  $projects
     * @return array<string, string>
     */
    protected function mapProjectsToOptions(array $projects): array
    {
        $projects = array_is_list($projects) ? $projects : [$projects];
        $options = [];

        foreach ($projects as $project) {
            if (! is_array($project)) {
                continue;
            }

            $id = Arr::get($project, 'id')
                ?? Arr::get($project, 'uuid')
                ?? Arr::get($project, 'projectId')
                ?? Arr::get($project, 'ProjectId');

            if (blank($id)) {
                continue;
            }

            $label = Arr::get($project, 'name')
                ?? Arr::get($project, 'projectName')
                ?? Arr::get($project, 'ProjectName')
                ?? Arr::get($project, 'title')
                ?? $id;

            $options[(string) $id] = (string) $label;
        }

        return $options;
    }

    /**
     * @param  array<int|string, mixed>  $groups
     * @return array<string, string>
     */
    protected function mapGroupsToOptions(array $groups): array
    {
        $groups = array_is_list($groups) ? $groups : [$groups];
        $options = [];

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            $id = Arr::get($group, 'id')
                ?? Arr::get($group, 'uuid')
                ?? Arr::get($group, 'groupId')
                ?? Arr::get($group, 'GroupId');

            if (blank($id)) {
                continue;
            }

            $label = Arr::get($group, 'name')
                ?? Arr::get($group, 'groupName')
                ?? Arr::get($group, 'GroupName')
                ?? $id;

            $options[(string) $id] = (string) $label;
        }

        return $options;
    }
}
