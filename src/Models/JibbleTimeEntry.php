<?php

namespace Gpos\FilamentJibble\Models;

use Gpos\FilamentJibble\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function getPictureUrlAttribute(): ?string
    {
        $placeholder = asset('images/jibble-avatar-placeholder.svg');

        if (! empty($this->picture_public_url)) {
            return $this->picture_public_url;
        }

        $fileId = $this->picture_file_id
            ?? data_get($this->picture, 'fileId');

        if (! $fileId) {
            return $placeholder;
        }

        $base = rtrim(config('jibble.storage_public_base', 'https://storage.prod.jibble.io'), '/');

        return $base . '/' . ltrim($fileId, '/');
    }
}
