<?php

namespace Gpos\FilamentJibble\Support;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibbleTimesheet;
use Gpos\FilamentJibble\Models\JibbleTimesheetSummary;
use Gpos\FilamentJibble\Support\TenantHelper;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        $tenantColumn = TenantHelper::tenantColumn();
        DB::transaction(function () use ($connection, $rows, $start, $end, $tenantColumn): void {
            $keepIds = [];
            $keepNull = false;

            foreach ($rows as $row) {
                $tracked = (int) ($row->tracked_seconds ?? 0);
                $billable = (int) ($row->billable_seconds ?? 0);
                $break = (int) ($row->break_seconds ?? 0);

                $jibblePersonId = $row->jibble_person_id;

                if ($tracked === 0 && $billable === 0) {
                    continue;
                }

                if ($jibblePersonId === null) {
                    $keepNull = true;
                } else {
                    $keepIds[] = $jibblePersonId;
                }

                $attributes = [
                    'connection_id' => $connection->id,
                    'period' => 'Range',
                    'date' => $start,
                    'jibble_person_id' => $jibblePersonId,
                ];

                $values = [
                    $tenantColumn => $connection->getTenantKey(),
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
                ];

                try {
                    $summary = JibbleTimesheetSummary::query()
                        ->withTrashed()
                        ->updateOrCreate($attributes, $values);
                } catch (QueryException $exception) {
                    if ($exception->getCode() !== '23505') {
                        throw $exception;
                    }

                    $summary = JibbleTimesheetSummary::query()
                        ->withTrashed()
                        ->where($attributes)
                        ->firstOrNew();

                    $summary->fill(array_merge($attributes, $values));
                    $summary->save();
                }

                if ($summary->trashed()) {
                    $summary->restore();
                }
            }

            $keepIds = array_values(array_unique($keepIds));

            $existing = JibbleTimesheetSummary::query()
                ->withTrashed()
                ->where('connection_id', $connection->id)
                ->where('period', 'Range')
                ->whereDate('date', $start)
                ->get();

            $idsToDelete = $existing->filter(function (JibbleTimesheetSummary $summary) use ($keepIds, $keepNull): bool {
                $personId = $summary->jibble_person_id;

                if ($personId === null) {
                    return ! $keepNull;
                }

                return ! in_array($personId, $keepIds, true);
            })->pluck('id');

            if ($idsToDelete->isNotEmpty()) {
                JibbleTimesheetSummary::query()
                    ->withTrashed()
                    ->whereIn('id', $idsToDelete)
                    ->forceDelete();
            }
        });
    }
}
