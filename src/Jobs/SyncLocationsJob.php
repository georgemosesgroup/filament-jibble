<?php

namespace Gpos\FilamentJibble\Jobs;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibbleLocation;
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

class SyncLocationsJob implements ShouldQueue
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
            Log::warning('SyncLocationsJob: connection not found', ['connection_id' => $this->connectionId]);

            return;
        }

        $organizationUuid = $connection->organization_uuid ?? config('jibble.organization_uuid');

        if (blank($organizationUuid)) {
            Log::error('SyncLocationsJob aborted: missing organization uuid', ['connection_id' => $this->connectionId]);

            return;
        }

        $log = JibbleSyncLog::create([
            'tenant_id' => $connection->tenant_id,
            'connection_id' => $connection->id,
            'resource' => 'locations',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $manager = $factory->makeManager($connection);

        try {
            $response = $manager->resource('locations')->list([], [
                'organization_uuid' => $organizationUuid,
            ]);
        } catch (Throwable $exception) {
            Log::error('SyncLocationsJob failed to fetch locations', [
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

        $this->persistItems($connection, $response->items());

        $nextLink = Arr::get($response->toResponse()->json(), '@odata.nextLink');

        while ($nextLink) {
            try {
                $nextResponse = $manager->client()->request('GET', $nextLink);
            } catch (Throwable $exception) {
                Log::error('SyncLocationsJob failed on next page', [
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

            $this->persistItems($connection, $items);

            $nextLink = Arr::get($data, '@odata.nextLink');
        }

        $log->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
    }

    protected function persistItems(JibbleConnection $connection, array $items): void
    {
        foreach ($items as $item) {
            $this->storeLocation($connection, (array) $item);
        }
    }

    protected function storeLocation(JibbleConnection $connection, array $payload): void
    {
        $locationId = Arr::get($payload, 'id');

        if (! $locationId) {
            return;
        }

        $latitude = Arr::get($payload, 'coordinates.latitude');
        $longitude = Arr::get($payload, 'coordinates.longitude');

        $geofence = Arr::get($payload, 'geoFence');

        $jibbleCreatedAt = $this->parseDateTime(Arr::get($payload, 'createdAt'));
        $jibbleUpdatedAt = $this->parseDateTime(Arr::get($payload, 'updatedAt'));

        JibbleLocation::query()->updateOrCreate([
            'connection_id' => $connection->id,
            'jibble_location_id' => $locationId,
        ], [
            'tenant_id' => $connection->tenant_id,
            'name' => Arr::get($payload, 'name'),
            'code' => Arr::get($payload, 'code'),
            'address' => Arr::get($payload, 'address'),
            'status' => Arr::get($payload, 'status'),
            'latitude' => is_numeric($latitude) ? (float) $latitude : null,
            'longitude' => is_numeric($longitude) ? (float) $longitude : null,
            'geofence_radius' => is_numeric(Arr::get($geofence, 'radius')) ? (int) Arr::get($geofence, 'radius') : null,
            'geofence_units' => Arr::get($geofence, 'units'),
            'geo_fence' => $geofence ?: null,
            'coordinates' => Arr::get($payload, 'coordinates') ?: null,
            'schedules' => Arr::get($payload, 'schedules') ?: null,
            'jibble_created_at' => $jibbleCreatedAt,
            'jibble_updated_at' => $jibbleUpdatedAt,
            'payload' => $payload,
        ]);
    }

    protected function parseDateTime(?string $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}

