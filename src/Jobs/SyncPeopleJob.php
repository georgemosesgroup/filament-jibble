<?php

namespace Gpos\FilamentJibble\Jobs;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibblePerson;
use Gpos\FilamentJibble\Services\Jibble\JibbleManager;
use Gpos\FilamentJibble\Support\JibbleConnectionFactory;
use Gpos\FilamentJibble\Models\JibbleSyncLog;
use Gpos\FilamentJibble\Support\TenantHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncPeopleJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $connectionId,
    ) {
        $this->onQueue(config('filament-jibble.sync.queue', 'default'));
    }

    public function handle(JibbleConnectionFactory $factory): void
    {
        /** @var JibbleConnection|null $connection */
        $connection = JibbleConnection::query()->find($this->connectionId);

        if (! $connection) {
            Log::warning('SyncPeopleJob: connection not found', ['connection_id' => $this->connectionId]);

            return;
        }

        $tenantColumn = TenantHelper::tenantColumn();

        $log = JibbleSyncLog::create([
            $tenantColumn => $connection->getTenantKey(),
            'connection_id' => $connection->id,
            'resource' => 'people',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $manager = $factory->makeManager($connection);
        $organizationUuid = $connection->organization_uuid ?? config('jibble.organization_uuid');

        Log::debug('SyncPeopleJob resolved organization UUID', [
            'connection_id' => $this->connectionId,
            'uuid' => $organizationUuid,
        ]);

        if (blank($organizationUuid)) {
            $message = 'Organization UUID is not configured for this connection.';

            Log::error('SyncPeopleJob aborted: missing organization uuid', [
                'connection_id' => $this->connectionId,
            ]);

            $log->update([
                'status' => 'failed',
                'message' => $message,
                'finished_at' => now(),
            ]);

            return;
        }

        try {
            $response = $manager->resource('people')->all([], [
                'organization_uuid' => $organizationUuid,
            ]);
        } catch (Throwable $exception) {
            Log::error('SyncPeopleJob failed to fetch people', [
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

        $items = $response->json('value') ?? $response->json();

        if (! is_array($items)) {
            $log->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            return;
        }

        $processedIds = [];

        foreach ($items as $item) {
            if ($this->storePerson($connection, (array) $item)) {
                $processedIds[] = Arr::get((array) $item, 'id');
            }
        }

        if ($connection->hasGroupFilter() && ! empty($processedIds)) {
            JibblePerson::query()
                ->where('connection_id', $connection->id)
                ->whereNotIn('jibble_id', $processedIds)
                ->delete();
        }

        $log->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
    }

    private function storePerson(JibbleConnection $connection, array $payload): bool
    {
        $tenantColumn = TenantHelper::tenantColumn();

        $jibbleId = Arr::get($payload, 'id');

        if (! $jibbleId) {
            return false;
        }

        $defaultGroupId = $connection->getDefaultGroupId();
        $personGroupId = Arr::get($payload, 'groupId')
            ?? Arr::get($payload, 'group.id');

        if ($defaultGroupId && $personGroupId !== $defaultGroupId) {
            return false;
        }

        JibblePerson::query()->updateOrCreate(
            [
                'connection_id' => $connection->id,
                'jibble_id' => $jibbleId,
            ],
            [
                $tenantColumn => $connection->getTenantKey(),
                'organization_id' => Arr::get($payload, 'organizationId'),
                'overridden_properties' => Arr::get($payload, 'overriddenProperties'),
                'calendar_id' => Arr::get($payload, 'calendarId'),
                'schedule_id' => Arr::get($payload, 'scheduleId'),
                'pay_period_definition_id' => Arr::get($payload, 'payPeriodDefinitionId'),
                'group_id' => Arr::get($payload, 'groupId') ?? Arr::get($payload, 'group.id'),
                'position_id' => Arr::get($payload, 'positionId') ?? Arr::get($payload, 'position.id'),
                'employment_type_id' => Arr::get($payload, 'employmentTypeId') ?? Arr::get($payload, 'employmentType.id'),
                'user_id' => Arr::get($payload, 'userId') ?? Arr::get($payload, 'user.id'),
                'email' => Arr::get($payload, 'email'),
                'phone_number' => Arr::get($payload, 'phoneNumber'),
                'country_code' => Arr::get($payload, 'countryCode'),
                'first_name' => Arr::get($payload, 'firstName'),
                'last_name' => Arr::get($payload, 'lastName'),
                'full_name' => Arr::get($payload, 'fullName'),
                'preferred_name' => Arr::get($payload, 'preferredName'),
                'role' => Arr::get($payload, 'role'),
                'code' => Arr::get($payload, 'code'),
                'pin_code' => Arr::get($payload, 'pinCode'),
                'status' => Arr::get($payload, 'status'),
                'has_embeddings' => Arr::get($payload, 'hasEmbeddings'),
                'nfc_token' => Arr::get($payload, 'nfcToken'),
                'work_start_date' => $this->parseDate(Arr::get($payload, 'workStartDate')),
                'join_date' => $this->parseDateTime(Arr::get($payload, 'joinDate')),
                'latest_time_entry_time' => $this->parseDateTime(Arr::get($payload, 'latestTimeEntryTime')),
                'invited_at' => $this->parseDateTime(Arr::get($payload, 'invitedAt')),
                'removed_at' => $this->parseDateTime(Arr::get($payload, 'removedAt')),
                'jibble_created_at' => $this->parseDateTime(Arr::get($payload, 'createdAt')),
                'jibble_updated_at' => $this->parseDateTime(Arr::get($payload, 'updatedAt')),
                'projects' => Arr::get($payload, 'projects'),
                'work_types' => Arr::get($payload, 'workTypes'),
                'managers' => Arr::get($payload, 'managers'),
                'unit_time_off_policies' => Arr::get($payload, 'unitTimeOffPolicies'),
                'picture' => Arr::get($payload, 'picture'),
                'managed_units' => Arr::get($payload, 'managedUnits'),
                'kiosks' => Arr::get($payload, 'kiosks'),
                'payload' => $payload,
            ],
        );

        return true;
    }

    private function parseDateTime(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
