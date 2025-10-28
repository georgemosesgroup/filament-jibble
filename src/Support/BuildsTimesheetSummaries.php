<?php

namespace Gpos\FilamentJibble\Support;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibbleTimesheet;
use Gpos\FilamentJibble\Models\JibbleTimesheetSummary;
use Gpos\FilamentJibble\Support\TenantHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

trait BuildsTimesheetSummaries
{
    protected function rebuildTimesheetSummaries(JibbleConnection $connection, Carbon $startDate, Carbon $endDate): void
    {
        $start = $startDate->toDateString();
        $end = $endDate->toDateString();

        $totals = JibbleTimesheet::query()
            ->where('connection_id', $connection->id)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('connection_id, person_id, jibble_person_id, SUM(tracked_seconds) as tracked_seconds, SUM(billable_seconds) as billable_seconds, SUM(break_seconds) as break_seconds')
            ->groupBy('connection_id', 'person_id', 'jibble_person_id')
            ->get();

        $this->persistSummaries($connection, $startDate, $endDate, $totals);
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    protected function persistSummaries(JibbleConnection $connection, Carbon $startDate, Carbon $endDate, Collection $rows): void
    {
        $start = $startDate->toDateString();
        $end = $endDate->toDateString();

        JibbleTimesheetSummary::query()
            ->withTrashed()
            ->where('connection_id', $connection->id)
            ->where('period', 'Range')
            ->whereDate('date', $start)
            ->forceDelete();

        $tenantColumn = TenantHelper::tenantColumn();
        $timestamp = now();
        $records = [];

        foreach ($rows as $row) {
            $tracked = (int) ($row->tracked_seconds ?? 0);
            $billable = (int) ($row->billable_seconds ?? 0);
            $break = (int) ($row->break_seconds ?? 0);

            if ($tracked === 0 && $billable === 0) {
                continue;
            }

            $records[] = [
                'connection_id' => $connection->id,
                'jibble_person_id' => $row->jibble_person_id,
                'date' => $start,
                'period' => 'Range',
                $tenantColumn => $connection->getTenantKey(),
                'person_id' => $row->person_id,
                'tracked_seconds' => $tracked,
                'payroll_seconds' => $billable,
                'regular_seconds' => $billable,
                'overtime_seconds' => max(0, $tracked - $billable),
                'daily_breakdown' => null,
                'summary' => json_encode([
                    'start_date' => $start,
                    'end_date' => $end,
                    'break_seconds' => $break,
                ]),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if (! empty($records)) {
            JibbleTimesheetSummary::query()->upsert(
                $records,
                ['connection_id', 'period', 'date', 'jibble_person_id'],
                [
                    $tenantColumn,
                    'person_id',
                    'tracked_seconds',
                    'payroll_seconds',
                    'regular_seconds',
                    'overtime_seconds',
                    'daily_breakdown',
                    'summary',
                    'updated_at',
                ]
            );
        }
    }
}
