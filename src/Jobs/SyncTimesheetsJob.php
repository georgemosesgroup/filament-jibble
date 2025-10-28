<?php

namespace Gpos\FilamentJibble\Jobs;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibblePerson;
use Gpos\FilamentJibble\Models\JibbleSyncLog;
use Gpos\FilamentJibble\Models\JibbleTimesheet;
use Gpos\FilamentJibble\Support\BuildsTimesheetSummaries;
use Gpos\FilamentJibble\Support\JibbleConnectionFactory;
use Gpos\FilamentJibble\Support\TenantHelper;
use Carbon\CarbonInterval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Throwable;

class SyncTimesheetsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use BuildsTimesheetSummaries;

    protected bool $periodProvided = false;

    protected int $maxChunkDays = 7;

    public function __construct(
        public readonly string $connectionId,
        public readonly array $query = [],
    ) {
        $this->onQueue(config('filament-jibble.sync.queue', 'default'));
    }

    public function handle(JibbleConnectionFactory $factory): void
    {
        /** @var JibbleConnection|null $connection */
        $connection = JibbleConnection::query()
            ->with('tenant')
            ->find($this->connectionId);

        if (! $connection) {
            Log::warning('SyncTimesheetsJob: connection not found', ['connection_id' => $this->connectionId]);

            return;
        }

        TenantHelper::forTenant($connection->tenant, function () use ($factory, $connection): void {
            $this->runForConnection($factory, $connection);
        });
    }

    private function runForConnection(JibbleConnectionFactory $factory, JibbleConnection $connection): void
    {
        $organizationUuid = $connection->organization_uuid ?? config('jibble.organization_uuid');

        if (blank($organizationUuid)) {
            Log::error('SyncTimesheetsJob aborted: missing organization uuid', ['connection_id' => $connection->id]);

            return;
        }

        $tenantColumn = TenantHelper::tenantColumn();

        $log = JibbleSyncLog::create([
            $tenantColumn => $connection->getTenantKey(),
            'connection_id' => $connection->id,
            'resource' => 'timesheets',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $manager = $factory->makeManager($connection);

        $baseQuery = $this->buildQuery();

        $batches = $this->personIdChunks($connection);

        $rangeStart = Carbon::parse($baseQuery['Date']);
        $rangeEnd = isset($baseQuery['EndDate'])
            ? Carbon::parse($baseQuery['EndDate'])
            : $rangeStart->copy();

        foreach ($this->chunkDateRange($rangeStart, $rangeEnd) as [$chunkStart, $chunkEnd]) {
            foreach ($batches as $personIds) {
                $query = $this->prepareChunkQuery($baseQuery, $chunkStart, $chunkEnd, $personIds);

                try {
                    $response = $manager->resource('timesheets')->list($query, [
                        'organization_uuid' => $organizationUuid,
                    ]);
                } catch (Throwable $exception) {
                    Log::error('SyncTimesheetsJob failed to fetch timesheets', [
                        'connection_id' => $connection->id,
                        'message' => $exception->getMessage(),
                        'date' => $query['Date'] ?? null,
                        'end_date' => $query['EndDate'] ?? null,
                    ]);

                    $log->update([
                        'status' => 'failed',
                        'message' => $exception->getMessage(),
                        'finished_at' => now(),
                    ]);

                    throw $exception;
                }

                $this->persistItems($connection, $response->items());

                $nextLink = Arr::get($response->toResponse()->json(), '@odata.nextLink');

                while ($nextLink) {
                    try {
                        $nextResponse = $manager->client()->request('GET', $nextLink);
                    } catch (Throwable $exception) {
                        Log::error('SyncTimesheetsJob failed on next page', [
                            'connection_id' => $connection->id,
                            'message' => $exception->getMessage(),
                            'date' => $query['Date'] ?? null,
                            'end_date' => $query['EndDate'] ?? null,
                        ]);

                        $log->update([
                            'status' => 'failed',
                            'message' => $exception->getMessage(),
                            'finished_at' => now(),
                        ]);

                        throw $exception;
                    }

                    $data = $nextResponse->json();
                    $items = Arr::get($data, 'value') ?? [];

                    $this->persistItems($connection, $items);

                    $nextLink = Arr::get($data, '@odata.nextLink');
                }
            }
        }

        $this->rebuildTimesheetSummaries($connection, $rangeStart, $rangeEnd);

        $log->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
    }

    protected function persistItems(JibbleConnection $connection, array $items): void
    {
        foreach ($items as $item) {
            $this->storeTimesheet($connection, (array) $item);
        }
    }

    protected function buildQuery(): array
    {
        $query = $this->query;

        $normalized = [];

        foreach ($query as $key => $value) {
            if (is_string($key)) {
                $normalized[strtolower($key)] = $value;
            }
        }

        $dateInput = $normalized['date'] ?? null;
        $endDateInput = $normalized['enddate'] ?? ($normalized['end_date'] ?? null);
        $periodInput = $normalized['period'] ?? null;

        $now = Carbon::now();
        $defaultStart = $now->copy()->startOfMonth()->toDateString();
        $date = $this->normalizeDate($dateInput ?? $defaultStart, Carbon::parse($defaultStart));

        if ($endDateInput !== null) {
            $endDate = $this->normalizeDate($endDateInput, Carbon::parse($date));
        } elseif ($dateInput === null) {
            $endDate = $this->normalizeDate($now->toDateString(), Carbon::parse($date));
        } else {
            $endDate = null;
        }

        $this->periodProvided = $periodInput !== null;

        $period = $periodInput
            ? ucfirst(strtolower((string) $periodInput))
            : ($endDate ? 'Custom' : 'Day');

        if ($endDate && Carbon::parse($endDate)->lt(Carbon::parse($date))) {
            $endDate = $date;
        }

        $result = [
            'Date' => $date,
            'Period' => $period,
        ];

        if ($endDate) {
            $result['EndDate'] = $endDate;
        } elseif ($period === 'Custom') {
            $result['EndDate'] = $date;
        }

        foreach ($query as $key => $value) {
            $lower = is_string($key) ? strtolower($key) : $key;

            if (in_array($lower, ['date', 'period', 'enddate', 'end_date'], true)) {
                continue;
            }

            $result[$this->normalizeQueryKey($key)] = $value;
        }

        return $result;
    }

    /**
     * @return array<int, array<int, string>|null>
     */
    protected function personIdChunks(JibbleConnection $connection): array
    {
        if ($this->hasPersonFilter()) {
            return [null];
        }

        $people = JibblePerson::query()
            ->where('connection_id', $connection->id)
            ->get();

        if ($connection->hasGroupFilter()) {
            $groupId = $connection->getDefaultGroupId();

            $people = $people->filter(function (JibblePerson $person) use ($groupId): bool {
                $payloadGroup = $person->payload['groupId']
                    ?? Arr::get($person->payload, 'group.id');

                return $payloadGroup === $groupId;
            });
        }

        $ids = $people
            ->pluck('jibble_id')
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            return [null];
        }

        return $ids->chunk(20)
            ->map(fn (Collection $chunk) => $chunk->values()->all())
            ->all();
    }

    protected function storeTimesheet(JibbleConnection $connection, array $payload): void
    {
        $personJibbleId = Arr::get($payload, 'person.id') ?? Arr::get($payload, 'personId');

        $person = null;

        if ($personJibbleId) {
            $person = JibblePerson::query()
                ->where('connection_id', $connection->id)
                ->where('jibble_id', $personJibbleId)
                ->first();
        }

        $defaultGroupId = $connection->getDefaultGroupId();

        if ($defaultGroupId) {
            $personGroupId = $person?->payload['groupId']
                ?? Arr::get($person?->payload, 'group.id');

            if ($personGroupId !== $defaultGroupId) {
                return;
            }
        }

        $summary = Arr::get($payload, 'summary', []);

        $dailyEntries = Arr::get($payload, 'daily', []);

        if (is_array($dailyEntries) && ! empty($dailyEntries)) {
            foreach ($dailyEntries as $daily) {
                if (! is_array($daily)) {
                    continue;
                }

                $this->storeTimesheetDay(
                    connection: $connection,
                    payload: $payload,
                    person: $person,
                    personJibbleId: $personJibbleId,
                    summary: $summary,
                    daily: $daily,
                );
            }

            return;
        }

        $this->storeTimesheetDay(
            connection: $connection,
            payload: $payload,
            person: $person,
            personJibbleId: $personJibbleId,
            summary: $summary,
            daily: null,
        );
    }

    protected function durationToSeconds(?string $duration): int
    {
        if (! is_string($duration) || trim($duration) === '') {
            return 0;
        }

        try {
            $interval = CarbonInterval::make($duration);
        } catch (Throwable) {
            $normalized = preg_replace('/\.(\d+)S$/', 'S', $duration);

            if (! is_string($normalized)) {
                return 0;
            }

            try {
                $interval = CarbonInterval::make($normalized);
            } catch (Throwable) {
                return 0;
            }
        }

        return (int) round($interval->totalSeconds);
    }

    protected function normalizeQueryKey(string|int $key): string|int
    {
        if (! is_string($key)) {
            return $key;
        }

        return match (strtolower($key)) {
            'personids', 'person_ids', 'personids[]', 'person_ids[]' => 'PersonIds',
            default => $key,
        };
    }

    protected function hasPersonFilter(): bool
    {
        foreach (array_keys($this->query) as $key) {
            if (! is_string($key)) {
                continue;
            }

            if (in_array(strtolower($key), ['personids', 'person_ids'], true)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeDate(string $date, ?Carbon $fallback = null): string
    {
        try {
            return Carbon::parse($date)->toDateString();
        } catch (Throwable) {
            return ($fallback ?? Carbon::now()->subDay())->toDateString();
        }
    }

    /**
     * @return array<int, array{0: Carbon, 1: Carbon}>
     */
    protected function chunkDateRange(Carbon $start, Carbon $end): array
    {
        $chunks = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $chunkEnd = $cursor->copy()->addDays($this->maxChunkDays - 1);

            if ($chunkEnd->gt($end)) {
                $chunkEnd = $end->copy();
            }

            $chunks[] = [$cursor->copy(), $chunkEnd];

            $cursor = $chunkEnd->copy()->addDay();
        }

        return $chunks;
    }

    protected function prepareChunkQuery(array $baseQuery, Carbon $chunkStart, Carbon $chunkEnd, ?array $personIds): array
    {
        $query = $baseQuery;

        $query['Date'] = $chunkStart->toDateString();

        if ($chunkStart->equalTo($chunkEnd)) {
            unset($query['EndDate']);

            if (! $this->periodProvided) {
                $query['Period'] = 'Day';
            }
        } else {
            $query['EndDate'] = $chunkEnd->toDateString();

            if (! $this->periodProvided) {
                $query['Period'] = 'Custom';
            }
        }

        if ($personIds !== null) {
            $query['PersonIds'] = $personIds;
        } elseif (! array_key_exists('PersonIds', $baseQuery)) {
            unset($query['PersonIds']);
        }

        return $query;
    }

    protected function storeTimesheetDay(
        JibbleConnection $connection,
        array $payload,
        ?JibblePerson $person,
        ?string $personJibbleId,
        array $summary,
        ?array $daily,
    ): void {
        $tenantColumn = TenantHelper::tenantColumn();

        $date = Arr::get($daily, 'date') ?? Arr::get($payload, 'date');

        if (! $date) {
            return;
        }

        try {
            $date = Carbon::parse($date)->toDateString();
        } catch (Throwable) {
            return;
        }

        $baseTimesheetId = Arr::get($payload, 'id') ?? Arr::get($payload, 'timesheetId');

        if ($baseTimesheetId) {
            $timesheetId = "{$baseTimesheetId}:{$date}";
        } elseif ($personJibbleId) {
            $timesheetId = "{$personJibbleId}:{$date}";
        } else {
            $timesheetId = "{$connection->id}:{$date}";
        }

        $trackedDuration = $daily
            ? Arr::get($daily, 'trackedHours.total')
                ?? Arr::get($daily, 'tracked')
                ?? Arr::get($summary, 'tracked')
                ?? Arr::get($payload, 'total')
            : Arr::get($summary, 'tracked') ?? Arr::get($payload, 'total');

        $breakDuration = $daily
            ? Arr::get($daily, 'trackedHours.totalBreakTime')
                ?? Arr::get($daily, 'breakTime')
                ?? Arr::get($summary, 'breakTime')
            : Arr::get($summary, 'breakTime');

        $billableDuration = $daily
            ? Arr::get($daily, 'payrollHours.total')
                ?? Arr::get($daily, 'payroll')
                ?? Arr::get($summary, 'payroll')
                ?? Arr::get($payload, 'totalPayroll')
            : Arr::get($summary, 'payroll') ?? Arr::get($payload, 'totalPayroll');

        $payloadForRecord = $payload;

        if ($daily) {
            $payloadForRecord['daily'] = [$daily];
        }

        JibbleTimesheet::query()->updateOrCreate([
            'connection_id' => $connection->id,
            'jibble_timesheet_id' => $timesheetId,
        ], [
            $tenantColumn => $connection->getTenantKey(),
            'person_id' => $person?->id,
            'jibble_person_id' => $personJibbleId,
            'date' => $date,
            'status' => Arr::get($payload, 'status'),
            'tracked_seconds' => $this->durationToSeconds($trackedDuration),
            'break_seconds' => $this->durationToSeconds($breakDuration),
            'billable_seconds' => $this->durationToSeconds($billableDuration),
            'segments' => $daily ? [$daily] : Arr::get($payload, 'daily'),
            'payload' => $payloadForRecord,
        ]);
    }
}
