<?php

namespace Gpos\FilamentJibble\Filament\Pages;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Jobs\SyncPeopleJob;
use Gpos\FilamentJibble\Jobs\SyncTimesheetSummaryJob;
use Gpos\FilamentJibble\Support\JibbleConnectionFactory;
use Gpos\FilamentJibble\Support\TenantHelper;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class JibbleProfileSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = null;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $title = null;

    protected string $view = 'filament-jibble::filament.pages.jibble-profile-settings';

    public ?JibbleConnection $connection = null;
    public array $organizationOptions = [];
    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(! $this->isMultitenant(), 404);

        $this->connection = $this->resolveConnection();
        $this->form->fill($this->connection?->toArray() ?? []);
        $state = $this->form->getState();
        $selected = Arr::get($state, 'organization_uuid');

        if (! $selected && $this->connection?->organization_uuid) {
            $selected = $this->connection->organization_uuid;
            $this->form->fill([
                ...$this->form->getState(),
                'organization_uuid' => $selected,
            ]);
        }

        $this->syncOrganizationOptions($selected);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament-jibble::resources.pages.profile.form.fields.name'))
                            ->default('primary')
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('organization_uuid')
                            ->label(__('filament-jibble::resources.pages.profile.form.fields.organization'))
                            ->options(fn (): array => $this->organizationOptions)
                            ->placeholder(__('filament-jibble::resources.pages.profile.form.placeholders.organization'))
                            ->searchable()
                            ->disabled(fn (): bool => empty($this->organizationOptions))
                            ->helperText(__('filament-jibble::resources.pages.profile.form.helpers.organization'))
                            ->hintAction(
                                Action::make('fetch-organizations')
                                    ->label(__('filament-jibble::resources.pages.profile.form.actions.fetch'))
                                    ->icon('heroicon-o-arrow-path')
                                    ->action('fetchOrganizations')
                            ),
                        Forms\Components\TextInput::make('client_id')
                            ->label(__('filament-jibble::resources.pages.profile.form.fields.client_id'))
                            ->nullable()
                            ->live()
                            ->dehydrated()
                            ->rules(function (Get $get): array {
                                return blank($get('api_token')) ? ['required'] : ['nullable'];
                            }),
                        Forms\Components\TextInput::make('client_secret')
                            ->label(__('filament-jibble::resources.pages.profile.form.fields.client_secret'))
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->live()
                            ->dehydrated()
                            ->rules(function (Get $get): array {
                                return blank($get('api_token')) ? ['required'] : ['nullable'];
                            }),
                        Forms\Components\Textarea::make('api_token')
                            ->label(__('filament-jibble::resources.pages.profile.form.fields.api_token'))
                            ->rows(3)
                            ->nullable()
                            ->live()
                            ->dehydrated()
                            ->rules(function (Get $get): array {
                                $clientId = $get('client_id');
                                $clientSecret = $get('client_secret');

                                return blank($clientId) && blank($clientSecret)
                                    ? ['required']
                                    : ['nullable'];
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function syncNow(): void
    {
        if (! $this->connection?->exists) {
            Notification::make()
                ->warning()
                ->title(__('filament-jibble::resources.pages.profile.notifications.no_connection.title'))
                ->body(__('filament-jibble::resources.pages.profile.notifications.no_connection.body'))
                ->send();

            return;
        }

        try {
            SyncPeopleJob::dispatch($this->connection->id);

            SyncTimesheetSummaryJob::dispatch($this->connection->id, [
                'Period' => 'Custom',
                'Date' => Carbon::today()->subDays(7)->format('Y-m-d'),
                'EndDate' => Carbon::today()->format('Y-m-d'),
            ]);

            Notification::make()
                ->success()
                ->title(__('filament-jibble::resources.pages.profile.notifications.sync_started.title'))
                ->body(__('filament-jibble::resources.pages.profile.notifications.sync_started.body'))
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title(__('filament-jibble::resources.pages.profile.notifications.sync_failed.title'))
                ->body(Str::limit($exception->getMessage(), 200))
                ->send();
        }
    }

    public function save(JibbleConnectionFactory $factory): void
    {
        $this->form->validate();
        $data = $this->form->getState();

        try {
            $connection = $this->connection ?? new JibbleConnection();
            $connection->fill(Arr::only($data, [
                'name',
                'organization_uuid',
                'client_id',
                'client_secret',
                'api_token',
            ]));
            $connection->setTenantKey(null);
            $connection->user_id = $this->user()?->getAuthIdentifier();
            if (blank($data['api_token']) && (! blank($data['client_id']) || ! blank($data['client_secret']))) {
                $connection->api_token = null;
            }
            if (! blank($connection->organization_uuid)) {
                $label = $this->organizationOptions[$connection->organization_uuid]
                    ?? $connection->organization_name
                    ?? $connection->getSettings('organization_label');
                if ($label) {
                    $connection->organization_name = $label;
                    $connection->settings = array_merge($connection->settings ?? [], [
                        'organization_label' => $label,
                    ]);
                }
            }
            $connection->save();
            $this->connection = $connection;
            $this->syncOrganizationOptions($connection->organization_uuid);

            Notification::make()
                ->success()
                ->title(__('filament-jibble::resources.pages.profile.notifications.saved.title'))
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->title(__('filament-jibble::resources.pages.profile.notifications.save_failed.title'))
                ->body($exception->getMessage())
                ->send();

            return;
        }

        try {
            $factory->makeManager($this->connection)->client()->request('GET', 'Organizations');

            Notification::make()
                ->success()
                ->title(__('filament-jibble::resources.pages.profile.notifications.verified.title'))
                ->body(__('filament-jibble::resources.pages.profile.notifications.verified.body'))
                ->send();

            $this->fetchOrganizations($factory, false);
        } catch (Throwable $exception) {
            Notification::make()
                ->warning()
                ->title(__('filament-jibble::resources.pages.profile.notifications.verify_failed.title'))
                ->body(Str::limit($exception->getMessage(), 200))
                ->send();
        }
    }

    protected function resolveConnection(): ?JibbleConnection
    {
        if ($this->isMultitenant()) {
            return null;
        }

        $userId = $this->user()?->getAuthIdentifier();

        if (! $userId) {
            return null;
        }

        return JibbleConnection::query()->firstOrNew([
            'user_id' => $userId,
        ], [
            'name' => 'primary',
        ]);
    }

    public function fetchOrganizations(JibbleConnectionFactory $factory, bool $notify = true): void
    {
        if (! $this->connection?->exists) {
            Notification::make()
                ->warning()
                ->title(__('filament-jibble::resources.pages.profile.notifications.save_first.title'))
                ->body(__('filament-jibble::resources.pages.profile.notifications.save_first.body'))
                ->send();

            return;
        }

        try {
            $response = $factory->makeManager($this->connection)->client()->request('GET', 'Organizations');
            $options = $this->mapOrganizationsToOptions($response->data());

            if (empty($options)) {
                if ($notify) {
                    Notification::make()
                        ->warning()
                        ->title(__('filament-jibble::resources.pages.profile.notifications.no_organizations.title'))
                        ->body(__('filament-jibble::resources.pages.profile.notifications.no_organizations.body'))
                        ->send();
                }

                return;
            }

            $state = $this->form->getState();
            $selected = Arr::get($state, 'organization_uuid') ?: $this->connection->organization_uuid;

            if (blank($selected) && count($options) === 1) {
                $selected = array_key_first($options);
            }

            if ($selected && ! isset($options[$selected])) {
                $options[$selected] = $selected;
            }

            $this->organizationOptions = $options;

            if ($selected) {
                $state['organization_uuid'] = $selected;
                $this->form->fill($state);
            }

            $this->syncOrganizationOptions($selected);

            if ($notify) {
                Notification::make()
                    ->success()
                    ->title(__('filament-jibble::resources.pages.profile.notifications.organizations_loaded.title'))
                    ->body(__('filament-jibble::resources.pages.profile.notifications.organizations_loaded.body'))
                    ->send();
            }
        } catch (Throwable $exception) {
            if ($notify) {
                Notification::make()
                    ->danger()
                    ->title(__('filament-jibble::resources.pages.profile.notifications.organizations_failed.title'))
                    ->body(Str::limit($exception->getMessage(), 200))
                    ->send();
            }
        }
    }

    protected function user(): Authenticatable|Model|null
    {
        return Filament::auth()->user();
    }

    protected function isMultitenant(): bool
    {
        return TenantHelper::current() !== null;
    }

    protected function syncOrganizationOptions(?string $selected): void
    {
        if (blank($selected)) {
            return;
        }

        if (! isset($this->organizationOptions[$selected])) {
            $label = $this->connection?->organization_name
                ?? $this->connection?->getSettings('organization_label', $selected);
            $this->organizationOptions[$selected] = $label ?: $selected;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>|array<string, mixed>  $organizations
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

            $name = Arr::get($organization, 'name')
                ?? Arr::get($organization, 'OrganizationName')
                ?? Arr::get($organization, 'display_name')
                ?? Arr::get($organization, 'slug')
                ?? $id;

            $options[(string) $id] = (string) $name;
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-jibble::resources.pages.profile.navigation_label');
    }

    public function getTitle(): string
    {
        return __('filament-jibble::resources.pages.profile.title');
    }
}
