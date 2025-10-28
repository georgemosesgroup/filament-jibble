<?php

namespace Gpos\FilamentJibble\Models;

use Gpos\FilamentJibble\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class JibbleTimeEntry extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'jibble_time_entries';

    protected $fillable = [
        'tenant_id',
        'connection_id',
        'person_id',
        'jibble_entry_id',
        'jibble_person_id',
        'project_id',
        'activity_id',
        'location_id',
        'kiosk_id',
        'break_id',
        'client_type',
        'type',
        'status',
        'note',
        'offset',
        'belongs_to_date',
        'time',
        'local_time',
        'is_offline',
        'is_face_recognized',
        'is_automatic',
        'is_manual',
        'is_outside_geofence',
        'is_manual_location',
        'is_unusual',
        'is_end_of_day',
        'is_from_speed_kiosk',
        'is_locked',
        'previous_entry_id',
        'next_entry_id',
        'coordinates',
        'picture',
        'picture_file_id',
        'picture_file_name',
        'picture_size',
        'picture_hash',
        'picture_public_url',
        'platform',
        'payload',
    ];

    protected $casts = [
        'belongs_to_date' => 'date',
        'time' => 'datetime',
        'local_time' => 'datetime',
        'is_offline' => 'bool',
        'is_face_recognized' => 'bool',
        'is_automatic' => 'bool',
        'is_manual' => 'bool',
        'is_outside_geofence' => 'bool',
        'is_manual_location' => 'bool',
        'is_unusual' => 'bool',
        'is_end_of_day' => 'bool',
        'is_from_speed_kiosk' => 'bool',
        'is_locked' => 'bool',
        'coordinates' => 'array',
        'picture' => 'array',
        'picture_size' => 'int',
        'platform' => 'array',
        'payload' => 'array',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(JibbleConnection::class, 'connection_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(JibblePerson::class, 'person_id');
    }

    public function location(): BelongsTo
    {
        $relation = $this->belongsTo(JibbleLocation::class, 'location_id', 'jibble_location_id');

        if ($this->connection_id) {
            $relation->where('connection_id', $this->connection_id);
        }

        return $relation;
    }

    public function getPictureUrlAttribute(): string
    {
        if (! empty($this->picture_public_url)) {
            return $this->picture_public_url;
        }

        $fileId = $this->picture_file_id
            ?? data_get($this->picture, 'fileId');

        if ($fileId) {
            $base = rtrim(config('jibble.storage_public_base', 'https://storage.prod.jibble.io'), '/');

            return $base.'/'.ltrim($fileId, '/');
        }

        return $this->generateAvatarPlaceholder();
    }

    private function generateAvatarPlaceholder(): string
    {
        $name = $this->person?->full_name
            ?? Arr::get($this->payload, 'person.fullName')
            ?? Arr::get($this->payload, 'person.full_name')
            ?? Arr::get($this->payload, 'fullName')
            ?? 'Jibble User';

        $initials = collect(preg_split('/\s+/', trim((string) $name) ?: ''))
            ->filter()
            ->map(fn (string $segment): string => Str::upper(Str::substr($segment, 0, 1)))
            ->take(2)
            ->implode('');

        if ($initials === '') {
            $initials = 'JB';
        }

        $background = $this->avatarBackgroundColor($name);
        $textColor = '#ffffff';

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120">
    <rect width="120" height="120" rx="60" fill="{$background}"/>
    <text x="50%" y="50%" dy="0.35em" text-anchor="middle" font-family="Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif" font-size="48" fill="{$textColor}" font-weight="600">{$initials}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function avatarBackgroundColor(string $name): string
    {
        $palette = [
            '#2563eb', // blue-600
            '#7c3aed', // violet-600
            '#db2777', // pink-600
            '#0891b2', // cyan-600
            '#16a34a', // green-600
            '#f97316', // orange-500
            '#0ea5e9', // sky-500
        ];

        $hash = crc32(Str::lower($name));

        return $palette[$hash % count($palette)];
    }
}
