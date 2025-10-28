<?php

namespace Gpos\FilamentJibble\Jobs;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Models\JibblePerson;
use Gpos\FilamentJibble\Services\Jibble\JibbleManager;
use Gpos\FilamentJibble\Support\JibbleConnectionFactory;
use Gpos\FilamentJibble\Models\JibbleSyncLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
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

        $log = JibbleSyncLog::create([
            'tenant_id' => $connection->tenant_id,
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
                'tenant_id' => $connection->tenant_id,
                'email' => Arr::get($payload, 'email'),
                'first_name' => Arr::get($payload, 'firstName'),
                'last_name' => Arr::get($payload, 'lastName'),
                'full_name' => Arr::get($payload, 'fullName'),
                'status' => Arr::get($payload, 'status'),
                'payload' => $payload,
            ],
        );

        return true;
    }
}
