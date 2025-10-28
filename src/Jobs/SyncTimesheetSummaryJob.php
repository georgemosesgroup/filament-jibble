<?php

namespace Gpos\FilamentJibble\Jobs;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibbleSyncLog;
use Gpos\FilamentJibble\Support\BuildsTimesheetSummaries;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncTimesheetSummaryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use BuildsTimesheetSummaries;

    public function __construct(
        public readonly string $connectionId,
        public readonly array $query = [],
    ) {
        $this->onQueue(config('filament-jibble.sync.queue', 'default'));
    }

    public function handle(): void
    {
        /** @var JibbleConnection|null $connection */
        $connection = JibbleConnection::query()->find($this->connectionId);

        if (! $connection) {
            Log::warning('SyncTimesheetSummaryJob: connection not found', ['connection_id' => $this->connectionId]);

            return;
        }

        $log = JibbleSyncLog::create([
            'tenant_id' => $connection->tenant_id,
            'connection_id' => $connection->id,
            'resource' => 'timesheets_summary',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $query = $this->buildQuery();

            $start = Carbon::parse($query['date']);
            $end = isset($query['endDate']) ? Carbon::parse($query['endDate']) : $start->copy();

            $this->rebuildTimesheetSummaries($connection, $start, $end);

            $log->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::error('SyncTimesheetSummaryJob failed', [
                'connection_id' => $this->connectionId,
                'message' => $exception->getMessage(),
            ]);

            $log->update([
                'status' => 'failed',
                'message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            throw $exception;
        }
    }

    protected function buildQuery(): array
    {
        $query = $this->query;

        $query['date'] ??= Carbon::now()->startOfMonth()->toDateString();
        $query['endDate'] ??= Carbon::now()->toDateString();

        return $query;
    }
}

