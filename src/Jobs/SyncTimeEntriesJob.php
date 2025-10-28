<?php

namespace Gpos\FilamentJibble\Jobs;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibblePerson;
use Gpos\FilamentJibble\Models\JibbleTimeEntry;
use Gpos\FilamentJibble\Models\JibbleSyncLog;
use Gpos\FilamentJibble\Support\JibbleConnectionFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncTimeEntriesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $connectionId,
        public readonly array $query = [],
    ) {
        $this->onQueue(config('filament-jibble.sync.queue', 'default'));
    }

    public function handle(JibbleConnectionFactory $factory): void
    {
        /** @var JibbleConnection|null $connection */
        $connection = JibbleConnection::query()->find($this->connectionId);

        if (! $connection) {
            Log::warning('SyncTimeEntriesJob: connection not found', ['connection_id' => $this->connectionId]);

            return;
        }

        $organizationUuid = $connection->organization_uuid ?? config('jibble.organization_uuid');

        if (blank($organizationUuid)) {
            Log::error('SyncTimeEntriesJob aborted: missing organization uuid', ['connection_id' => $this->connectionId]);

            return;
        }

        $log = JibbleSyncLog::create([
            'tenant_id' => $connection->tenant_id,
            'connection_id' => $connection->id,
            'resource' => 'time_entries',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $manager = $factory->makeManager($connection);

        try {
            $response = $manager->resource('time_entries')->list($this->query, [
                'organization_uuid' => $organizationUuid,
            ]);
        } catch (Throwable $exception) {
            Log::error('SyncTimeEntriesJob failed to fetch time entries', [
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

        $this->processResponse($connection, $response->items());

        $nextLink = Arr::get($response->toResponse()->json(), '@odata.nextLink');

        while ($nextLink) {
            try {
                $nextResponse = $manager->client()->request('GET', $nextLink);
            } catch (Throwable $exception) {
                Log::error('SyncTimeEntriesJob failed on next page', [
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

            $data = $nextResponse->json();
            $items = Arr::get($data, 'value') ?? (is_array($data) ? $data : []);

            $this->processResponse($connection, $items);

            $nextLink = Arr::get($data, '@odata.nextLink');
        }

        $log->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
    }

    protected function processResponse(JibbleConnection $connection, array $items): void
    {
        foreach ($items as $item) {
            $this->storeEntry($connection, (array) $item);
        }
    }

    protected function storeEntry(JibbleConnection $connection, array $payload): void
    {
        $entryId = Arr::get($payload, 'id');

        if (! $entryId) {
            return;
        }

        $personJibbleId = Arr::get($payload, 'personId');

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
        $defaultProjectId = $connection->getDefaultProjectId();
        $projectId = Arr::get($payload, 'projectId');

        if ($defaultProjectId && is_string($projectId) && $projectId !== $defaultProjectId) {
            return;
        }

        if ($defaultProjectId && blank($projectId)) {
            $projectId = $defaultProjectId;
            Arr::set($payload, 'projectId', $projectId);
        }

        $picture = Arr::get($payload, 'picture', []);

        $pictureFileId = $picture['fileId'] ?? null;

        $pictureUrl = null;

        if ($pictureFileId) {
            $base = rtrim(config('jibble.storage_public_base', 'https://storage.prod.jibble.io'), '/');
            $pictureUrl = $base . '/' . ltrim($pictureFileId, '/');
        }

        $pictureSize = isset($picture['size']) && is_numeric($picture['size'])
            ? (int) $picture['size']
            : null;

        JibbleTimeEntry::query()->updateOrCreate([
            'connection_id' => $connection->id,
            'jibble_entry_id' => $entryId,
        ], [
            'tenant_id' => $connection->tenant_id,
            'person_id' => $person?->id,
            'jibble_person_id' => $personJibbleId,
            'project_id' => $projectId,
            'activity_id' => Arr::get($payload, 'activityId'),
            'location_id' => Arr::get($payload, 'locationId'),
            'kiosk_id' => Arr::get($payload, 'kioskId'),
            'break_id' => Arr::get($payload, 'breakId'),
            'client_type' => Arr::get($payload, 'clientType'),
            'type' => Arr::get($payload, 'type'),
            'status' => Arr::get($payload, 'status'),
            'note' => Arr::get($payload, 'note'),
            'offset' => Arr::get($payload, 'offset'),
            'belongs_to_date' => Arr::get($payload, 'belongsToDate'),
            'time' => $this->parseDateTime(Arr::get($payload, 'time')),
            'local_time' => $this->parseDateTime(Arr::get($payload, 'localTime')),
            'is_offline' => (bool) Arr::get($payload, 'isOffline', false),
            'is_face_recognized' => (bool) Arr::get($payload, 'isFaceRecognized', false),
            'is_automatic' => (bool) Arr::get($payload, 'isAutomatic', false),
            'is_manual' => (bool) Arr::get($payload, 'isManual', false),
            'is_outside_geofence' => (bool) Arr::get($payload, 'isOutsideGeofence', false),
            'is_manual_location' => (bool) Arr::get($payload, 'isManualLocation', false),
            'is_unusual' => (bool) Arr::get($payload, 'isUnusual', false),
            'is_end_of_day' => (bool) Arr::get($payload, 'isEndOfDay', false),
            'is_from_speed_kiosk' => (bool) Arr::get($payload, 'isFromSpeedKiosk', false),
            'is_locked' => (bool) Arr::get($payload, 'isLocked', false),
            'previous_entry_id' => Arr::get($payload, 'previousTimeEntryId'),
            'next_entry_id' => Arr::get($payload, 'nextTimeEntryId'),
            'coordinates' => Arr::get($payload, 'coordinates'),
            'picture' => $picture ?: null,
            'picture_file_id' => $pictureFileId,
            'picture_file_name' => $picture['fileName'] ?? null,
            'picture_size' => $pictureSize,
            'picture_hash' => $picture['hash'] ?? null,
            'picture_public_url' => $pictureUrl,
            'platform' => Arr::get($payload, 'platform'),
            'payload' => $payload,
        ]);
    }

    protected function parseDateTime(?string $value): ?Carbon
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
}
