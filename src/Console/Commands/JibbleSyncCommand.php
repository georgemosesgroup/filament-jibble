<?php

namespace Gpos\FilamentJibble\Console\Commands;

use Gpos\FilamentJibble\Jobs\SyncLocationsJob;
use Gpos\FilamentJibble\Jobs\SyncPeopleJob;
use Gpos\FilamentJibble\Jobs\SyncTimeEntriesJob;
use Gpos\FilamentJibble\Jobs\SyncTimesheetsJob;
use Gpos\FilamentJibble\Jobs\SyncTimesheetSummaryJob;
use Gpos\FilamentJibble\Models\JibbleConnection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class JibbleSyncCommand extends Command
{
    protected $signature = 'jibble:sync
        {connection? : Connection UUID}
        {--resource=all : Resource to sync (people,locations,time_entries,timesheets,timesheets_summary,all)}
        {--date= : Start date (YYYY-MM-DD)}
        {--end-date= : End date (YYYY-MM-DD)}
        ';

    protected $description = 'Dispatch sync jobs for Jibble resources.';

    public function handle(): int
    {
        $connections = $this->resolveConnections();

        if ($connections->isEmpty()) {
            $this->warn('No Jibble connections found.');

            return self::SUCCESS;
        }

        $resource = $this->option('resource');
        $date = $this->option('date');
        $endDate = $this->option('end-date');

        foreach ($connections as $connection) {
            $this->line("Dispatching sync for connection {$connection->name} ({$connection->id})");

            $jobs = [];

            if ($resource === 'people' || $resource === 'all') {
                $jobs[] = new SyncPeopleJob($connection->id);
            }

            if ($resource === 'locations' || $resource === 'all') {
                $jobs[] = new SyncLocationsJob($connection->id);
            }

            if ($resource === 'time_entries' || $resource === 'all') {
                $jobs[] = new SyncTimeEntriesJob($connection->id, array_filter([
                    'Date' => $date,
                    'EndDate' => $endDate,
                ]));
            }

            if ($resource === 'timesheets' || $resource === 'all') {
                $jobs[] = new SyncTimesheetsJob($connection->id, array_filter([
                    'Date' => $date,
                    'EndDate' => $endDate,
                ]));
            }

            if ($resource === 'timesheets_summary') {
                $jobs[] = new SyncTimesheetSummaryJob($connection->id, array_filter([
                    'Period' => $endDate ? 'Custom' : 'Day',
                    'Date' => $date,
                    'EndDate' => $endDate,
                ]));
            }

            if (empty($jobs)) {
                $this->warn('No resources selected for sync.');

                continue;
            }

            if (count($jobs) === 1) {
                dispatch($jobs[0]);

                continue;
            }

            Bus::chain($jobs)->dispatch();
        }

        $this->info('Sync jobs dispatched.');

        return self::SUCCESS;
    }

    private function resolveConnections()
    {
        $query = JibbleConnection::query();

        if ($connectionId = $this->argument('connection')) {
            $query->whereKey($connectionId);
        }

        return $query->get();
    }
}
