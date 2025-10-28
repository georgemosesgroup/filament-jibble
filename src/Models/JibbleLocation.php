<?php

namespace Gpos\FilamentJibble\Models;

use Gpos\FilamentJibble\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JibbleLocation extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'jibble_locations';

    protected $fillable = [
        'tenant_id',
        'connection_id',
        'jibble_location_id',
        'name',
        'code',
        'address',
        'status',
        'latitude',
        'longitude',
        'geofence_radius',
        'geofence_units',
        'geo_fence',
        'coordinates',
        'schedules',
        'jibble_created_at',
        'jibble_updated_at',
        'payload',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'geo_fence' => 'array',
        'coordinates' => 'array',
        'schedules' => 'array',
        'jibble_created_at' => 'immutable_datetime',
        'jibble_updated_at' => 'immutable_datetime',
        'payload' => 'array',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(JibbleConnection::class, 'connection_id');
    }
}
