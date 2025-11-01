<?php

namespace Gpos\FilamentJibble\Filament\Widgets;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Gpos\FilamentJibble\Models\JibblePerson;
use Gpos\FilamentJibble\Models\JibbleTimesheet;
use Gpos\FilamentJibble\Models\JibbleTimeEntry;
use Gpos\FilamentJibble\Support\TenantHelper;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TimesheetHeatmap extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament-jibble::filament.widgets.timesheet-heatmap';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public bool $requiresTenant = false;

    public string $month;

    public string $search = '';

    public array $formData = [];

    /**
     * @var array<int, array{date: string, day: string, label: string, is_today: bool}>
     */
    public array $days = [];

    /**
     * @var array<int, array{name: string, email: ?string, connection: ?string, initials: string, slots: array<int, array>, total_minutes: int, total_formatted: string}>
     */
    public array $people = [];

    /**
     * @var array<int, array{name: string, email: ?string, connection: ?string, initials: string, slots: array<int, array>, total_minutes: int, total_formatted: string}>
     */
    public array $allPeople = [];

    public bool $hasAnyPeople = false;

    protected int $targetMinutes = 480; // 8h target

    public function mount(): void
    {
        $this->requiresTenant = $this->panelHasTenancy();
        $this->month = now()->format('Y-m');
        $this->search = '';
        $this->syncFormState();
        $this->loadData();
    }

    protected function loadData(): void
    {
        $tenant = TenantHelper::current();

        if ($this->requiresTenant && ! $tenant) {
            $this->days = [];
            $this->people = [];
            $this->allPeople = [];
            $this->hasAnyPeople = false;

            Log::debug('TimesheetHeatmap: tenant required but not selected, widget hidden.');

            return;
        }

        $tenantColumn = TenantHelper::tenantColumn();

        try {
            $start = Carbon::createFromFormat('Y-m', $this->month, config('app.timezone'))
                ->startOfMonth()
                ->startOfDay();
        } catch (\Exception $exception) {
            Log::warning('TimesheetHeatmap: invalid month value, fallback to current month.', [
                'month' => $this->month ?? null,
                'exception' => $exception->getMessage(),
            ]);

            $start = now()->startOfMonth()->startOfDay();
            $this->month = $start->format('Y-m');
            $this->syncFormState();
        }

        $end = $start->copy()->endOfMonth()->endOfDay();

        $this->days = $this->generateDayGrid($start, $end);

        try {
            $personQuery = JibblePerson::query()
                ->when($tenant, fn ($query) => $query->where($tenantColumn, $tenant->getKey()))
                ->with([
                    'connection',
                    'timesheets' => function ($query) use ($start, $end) {
                        $query
                            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                            ->orderBy('date');
                    },
                ])
                ->orderBy('full_name')
                ->orderBy('email');

            $people = $personQuery->get();

            $latestEntries = collect();

            if ($people->isNotEmpty()) {
                $latestEntries = JibbleTimeEntry::query()
                    ->whereIn('person_id', $people->pluck('id'))
                    ->whereNull('deleted_at')
                    ->orderByDesc('time')
                    ->orderByDesc('created_at')
                    ->get()
                    ->unique('person_id')
                    ->keyBy('person_id');
            }

            $this->allPeople = $people
                ->map(function (JibblePerson $person) use ($latestEntries): array {
                    if ($latestEntries->has($person->getKey())) {
                        $person->setRelation('latestTimeEntry', $latestEntries->get($person->getKey()));
                    }

                    return $this->mapPerson($person);
                })
                ->all();

            $this->applySearchFilter();

            $this->hasAnyPeople = ! empty($this->allPeople);

            Log::debug('TimesheetHeatmap: data loaded', [
                'people_count' => count($this->allPeople),
                'tenant' => $tenant?->getKey(),
                'month' => $this->month,
            ]);
        } catch (\Throwable $exception) {
            Log::error('TimesheetHeatmap: failed to load data', [
                'tenant' => $tenant?->getKey(),
                'month' => $this->month,
                'exception' => $exception,
            ]);

            $this->days = [];
            $this->people = [];
            $this->allPeople = [];
            $this->hasAnyPeople = false;
        }
    }

    /**
     * @return array<int, array{date: string, day: string, label: string, is_today: bool}>
     */
    protected function generateDayGrid(Carbon $start, Carbon $end): array
    {
        $days = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $days[] = [
                'date' => $cursor->toDateString(),
                'day' => $cursor->isoFormat('dd'),
                'label' => $cursor->format('j'),
                'is_today' => $cursor->isSameDay(now()),
            ];

            $cursor->addDay();
        }

        return $days;
    }

    protected function mapPerson(JibblePerson $person): array
    {
        $timesheets = $person->timesheets
            ->mapWithKeys(fn ($timesheet) => [
                Carbon::parse($timesheet->date)->toDateString() => $timesheet,
            ]);

        $slots = [];
        $totalMinutes = 0;

        foreach ($this->days as $day) {
            $timesheet = $timesheets->get($day['date']);
            $minutes = $timesheet?->tracked_seconds !== null
                ? (int) floor($timesheet->tracked_seconds / 60)
                : null;

            $overtimeMinutes = 0;
            if ($minutes !== null) {
                $totalMinutes += $minutes;
                $overtimeMinutes = max(0, $minutes - $this->targetMinutes);
            }

            $slots[] = $this->mapTimesheetSlot(
                date: $day['date'],
                minutes: $minutes,
                overtime: $overtimeMinutes,
            );
        }

        $displayName = $person->full_name
            ?: trim(($person->first_name ?? '').' '.($person->last_name ?? ''))
                ?: ($person->email ?? __('filament-jibble::resources.widgets.timesheet_heatmap.employee'));

        return [
            'id' => $person->getKey(),
            'name' => $displayName,
            'email' => $person->email,
            'connection' => $person->relationLoaded('connection') ? optional($person->connection)->name : null,
            'initials' => $this->initials($displayName),
            'slots' => $slots,
            'total_minutes' => $totalMinutes,
            'total_formatted' => $this->formatMinutes($totalMinutes),
            'is_online' => $person->isOnline(),
        ];
    }

    protected function applySearchFilter(): void
    {
        if (blank($this->search)) {
            $this->people = $this->allPeople;
            return;
        }

        $needle = Str::lower(trim($this->search));

        $this->people = collect($this->allPeople)
            ->filter(function (array $person) use ($needle): bool {
                $haystack = Str::lower(
                    ($person['name'] ?? '').' '.($person['email'] ?? '').' '.($person['connection'] ?? '')
                );

                return Str::contains($haystack, $needle);
            })
            ->values()
            ->all();
    }

    /**
     * @return array{status: string, classes: string, tooltip: string, minutes_formatted: string}
     */
    protected function mapTimesheetSlot(string $date, ?int $minutes, ?int $overtime): array
    {
        $status = $this->determineStatus($minutes, (int) ($overtime ?? 0));
        $styles = $this->statusStyles()[$status] ?? $this->statusStyles()['missing'];

        return [
            'status' => $status,
            'classes' => $styles['slot_classes'],
            'style' => '',
            'tooltip' => $this->buildTooltip($styles['label'], $minutes, $date),
            'minutes_formatted' => $this->formatMinutes($minutes),
            'icon' => $styles['icon'] ?? null,
            'icon_classes' => $styles['slot_icon_classes'] ?? '',
        ];
    }

    protected function buildTooltip(string $label, ?int $minutes, string $date): string
    {
        $formattedDate = Carbon::parse($date)->toFormattedDateString();
        $minutesText = $minutes === null
            ? __('filament-jibble::resources.widgets.timesheet_heatmap.tooltip.no_data')
            : $this->formatMinutes($minutes);

        return "{$formattedDate} • {$label} • {$minutesText}";
    }

    protected function determineStatus(?int $minutes, int $overtime): string
    {
        if ($minutes === null) {
            return 'missing';
        }

        if ($minutes === 0) {
            return 'off';
        }

        if ($minutes <= 360) { // до 6h
            return 'target';
        }

        if ($minutes <= 480) { // 6–8h
            return 'extended';
        }

        if ($minutes <= 600) { // 8–10h
            return 'overtime';
        }

        return 'excessive'; // 10h+
    }

    protected function formatMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        if ($minutes === 0) {
            return '0h 00m';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%dh %02dm', $hours, $mins);
    }

    protected function initials(string $name): string
    {
        return Str::of($name)
            ->split('/[\s\-]+/')
            ->map(fn($part) => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)
            ->implode('');
    }

    /**
     * @return array<string, array{classes: string, label: string}>
     */
    protected function statusStyles(): array
    {
        return [
            'missing' => [
                'slot_classes' => 'fi-timesheet-slot fi-timesheet-slot--missing',
                'legend_classes' => 'fi-timesheet-slot fi-timesheet-slot--legend fi-timesheet-slot--missing',
                'label' => __('filament-jibble::resources.widgets.timesheet_heatmap.statuses.missing'),
            ],
            'off' => [
                'slot_classes' => 'fi-timesheet-slot fi-timesheet-slot--off',
                'legend_classes' => 'fi-timesheet-slot fi-timesheet-slot--legend fi-timesheet-slot--off',
                'label' => __('filament-jibble::resources.widgets.timesheet_heatmap.statuses.off'),
                'icon' => '×',
                'slot_icon_classes' => 'text-[10px] font-bold leading-none',
                'legend_icon_classes' => 'text-[7px] font-bold leading-none',
            ],
            'target' => [
                'slot_classes' => 'fi-timesheet-slot fi-timesheet-slot--target',
                'legend_classes' => 'fi-timesheet-slot fi-timesheet-slot--legend fi-timesheet-slot--target',
                'label' => __('filament-jibble::resources.widgets.timesheet_heatmap.statuses.target'),
            ],
            'extended' => [
                'slot_classes' => 'fi-timesheet-slot fi-timesheet-slot--extended',
                'legend_classes' => 'fi-timesheet-slot fi-timesheet-slot--legend fi-timesheet-slot--extended',
                'label' => __('filament-jibble::resources.widgets.timesheet_heatmap.statuses.extended'),
            ],
            'overtime' => [
                'slot_classes' => 'fi-timesheet-slot fi-timesheet-slot--overtime',
                'legend_classes' => 'fi-timesheet-slot fi-timesheet-slot--legend fi-timesheet-slot--overtime',
                'label' => __('filament-jibble::resources.widgets.timesheet_heatmap.statuses.overtime'),
            ],
            'excessive' => [
                'slot_classes' => 'fi-timesheet-slot fi-timesheet-slot--excessive',
                'legend_classes' => 'fi-timesheet-slot fi-timesheet-slot--legend fi-timesheet-slot--excessive',
                'label' => __('filament-jibble::resources.widgets.timesheet_heatmap.statuses.excessive'),
            ],
        ];
    }

    /**
     * @return array<int, array{key: string, classes: string, label: string}>
     */
    public function getLegendProperty(): array
    {
        $order = ['target', 'extended', 'overtime', 'excessive', 'off', 'missing'];
        $styles = $this->statusStyles();

        return collect($order)
            ->filter(fn(string $key) => isset($styles[$key]))
            ->map(fn(string $key) => [
                'classes' => $styles[$key]['legend_classes'],
                'style' => '',
                'label' => $styles[$key]['label'],
                'icon' => $styles[$key]['icon'] ?? null,
                'icon_classes' => $styles[$key]['legend_icon_classes'] ?? 'text-[7px] font-bold leading-none',
            ])
            ->values()
            ->all();
    }

    public function getAllPeopleCountProperty(): int
    {
        return count($this->allPeople);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)->schema([
                    TextInput::make('search')
                        ->label(false)
                        ->placeholder(__('filament-jibble::resources.widgets.timesheet_heatmap.search_placeholder'))
                        ->prefixIcon('heroicon-m-magnifying-glass')
                        ->prefixIconColor('gray')
                        ->extraAttributes(['class' => 'rounded-xl'])
                        ->live(debounce: 500)
                        ->afterStateUpdated(function (?string $state): void {
                            $this->search = $state ?? '';
                            $this->applySearchFilter();
                        })
                        ->columnSpan(['default' => 12, 'lg' => 6]),
                    Select::make('month_part')
                        ->label(__('filament-jibble::resources.widgets.timesheet_heatmap.month'))
                        ->options($this->getMonthOptions())
                        ->required()
                        ->default(now()->format('m'))
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(fn () => $this->updateMonthFromParts())
                        ->native(false)
                        ->columnSpan(['default' => 6, 'lg' => 3]),
                    Select::make('year_part')
                        ->label(__('filament-jibble::resources.widgets.timesheet_heatmap.year'))
                        ->options($this->getYearOptions())
                        ->required()
                        ->default(now()->format('Y'))
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(fn () => $this->updateMonthFromParts())
                        ->native(false)
                        ->columnSpan(['default' => 6, 'lg' => 3]),
                ])->columns(12),
            ])
            ->statePath('formData')
            ->inlineLabel(false);
    }

    protected function syncFormState(): void
    {
        $this->form->fill([
            'search' => $this->search,
            'month_part' => substr($this->month, 5, 2),
            'year_part' => substr($this->month, 0, 4),
        ]);
    }

    public function submit(): void
    {
        // Form has no submit action; method provided to satisfy Livewire handler.
    }

    protected function updateMonthFromParts(): void
    {
        $state = $this->form->getState();
        $year = $state['year_part'] ?? now()->format('Y');
        $month = $state['month_part'] ?? now()->format('m');

        $value = sprintf('%s-%s', $year, $month);

        if ($value === $this->month) {
            return;
        }

        $this->month = $value;
        $this->loadData();
    }

    protected function getMonthOptions(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(function (int $month): array {
                $value = str_pad((string) $month, 2, '0', STR_PAD_LEFT);

                return [
                    $value => Carbon::create(null, $month, 1)->isoFormat('MMMM'),
                ];
            })
            ->toArray();
    }

    protected function getYearOptions(): array
    {
        $tenant = TenantHelper::current();

        $query = JibbleTimesheet::query();

        if ($this->requiresTenant && ! $tenant) {
            $current = now()->format('Y');

            return [$current => $current];
        }

        $tenantColumn = TenantHelper::tenantColumn();

        if ($tenant) {
            $query->where($tenantColumn, $tenant->getKey());
        }

        $earliestDate = $query->min('date');
        $startYear = $earliestDate ? Carbon::parse($earliestDate)->year : now()->year;
        $endYear = now()->year;

        $years = [];
        for ($year = $endYear; $year >= $startYear; $year--) {
            $years[(string) $year] = (string) $year;
        }

        if (empty($years)) {
            $years[(string) $endYear] = (string) $endYear;
        }

        return $years;
    }

    protected function panelHasTenancy(): bool
    {
        return (bool) optional(Filament::getCurrentPanel())->hasTenancy();
    }
}