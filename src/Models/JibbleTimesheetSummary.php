<?php

namespace Gpos\FilamentJibble\Models;

use Gpos\FilamentJibble\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JibbleTimesheetSummary extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'jibble_timesheet_summaries';

    protected $fillable = [
        'tenant_id',
        'connection_id',
        'person_id',
        'jibble_person_id',
        'date',
        'period',
        'tracked_seconds',
        'payroll_seconds',
        'regular_seconds',
        'overtime_seconds',
        'daily_breakdown',
        'summary',
    ];

    protected $casts = [
        'date' => 'date',
        'daily_breakdown' => 'array',
        'summary' => 'array',
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
