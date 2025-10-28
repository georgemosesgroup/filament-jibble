<?php

namespace Gpos\FilamentJibble\Models;

use Gpos\FilamentJibble\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JibbleTimesheet extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'jibble_timesheets';

    protected $fillable = [
        'tenant_id',
        'connection_id',
        'person_id',
        'jibble_timesheet_id',
        'jibble_person_id',
        'date',
        'status',
        'tracked_seconds',
        'break_seconds',
        'billable_seconds',
        'segments',
        'payload',
    ];

    protected $casts = [
        'date' => 'date',
        'segments' => 'array',
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
}
