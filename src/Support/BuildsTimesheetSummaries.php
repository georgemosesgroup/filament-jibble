<?php

namespace Gpos\FilamentJibble\Support;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibbleTimesheet;
use Gpos\FilamentJibble\Models\JibbleTimesheetSummary;
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
            ->where('connection_id', $connection->id)
            ->where('period', 'Range')
            ->whereDate('date', $start)
            ->delete();

        foreach ($rows as $row) {
            $tracked = (int) ($row->tracked_seconds ?? 0);
            $billable = (int) ($row->billable_seconds ?? 0);
            $break = (int) ($row->break_seconds ?? 0);

            if ($tracked === 0 && $billable === 0) {
                continue;
            }

            JibbleTimesheetSummary::query()->updateOrCreate([
                'connection_id' => $connection->id,
                'jibble_person_id' => $row->jibble_person_id,
                'date' => $start,
                'period' => 'Range',
            ], [
                'tenant_id' => $connection->tenant_id,
                'person_id' => $row->person_id,
                'tracked_seconds' => $tracked,
                'payroll_seconds' => $billable,
                'regular_seconds' => $billable,
                'overtime_seconds' => max(0, $tracked - $billable),
                'daily_breakdown' => null,
                'summary' => [
                    'start_date' => $start,
                    'end_date' => $end,
                    'break_seconds' => $break,
                ],
            ]);
        }
    }
}
