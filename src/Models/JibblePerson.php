<?php

namespace Gpos\FilamentJibble\Models;

use Gpos\FilamentJibble\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Gpos\FilamentJibble\Models\JibbleTimesheet;

class JibblePerson extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use BelongsToTenant;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'jibble_people';

    protected $fillable = [
        'tenant_id',
        'connection_id',
        'jibble_id',
        'email',
        'first_name',
        'last_name',
        'full_name',
        'status',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(JibbleConnection::class, 'connection_id');
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(JibbleTimesheet::class, 'person_id');
    }
}
