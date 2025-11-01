<?php

namespace Gpos\FilamentJibble\Models;

use Gpos\FilamentJibble\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Gpos\FilamentJibble\Models\JibbleTimesheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

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
        'organization_id',
        'overridden_properties',
        'calendar_id',
        'schedule_id',
        'pay_period_definition_id',
        'group_id',
        'position_id',
        'employment_type_id',
        'user_id',
        'email',
        'phone_number',
        'country_code',
        'first_name',
        'last_name',
        'full_name',
        'preferred_name',
        'role',
        'code',
        'pin_code',
        'status',
        'has_embeddings',
        'nfc_token',
        'work_start_date',
        'join_date',
        'latest_time_entry_time',
        'invited_at',
        'removed_at',
        'jibble_created_at',
        'jibble_updated_at',
        'payload',
        'projects',
        'work_types',
        'managers',
        'unit_time_off_policies',
        'picture',
        'managed_units',
        'kiosks',
    ];

    protected $casts = [
        'payload' => 'array',
        'overridden_properties' => 'array',
        'projects' => 'array',
        'work_types' => 'array',
        'managers' => 'array',
        'unit_time_off_policies' => 'array',
        'picture' => 'array',
        'managed_units' => 'array',
        'kiosks' => 'array',
        'has_embeddings' => 'boolean',
        'work_start_date' => 'date',
        'join_date' => 'datetime',
        'latest_time_entry_time' => 'datetime',
        'invited_at' => 'datetime',
        'removed_at' => 'datetime',
        'jibble_created_at' => 'datetime',
        'jibble_updated_at' => 'datetime',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(JibbleConnection::class, 'connection_id');
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(JibbleTimesheet::class, 'person_id');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(JibbleTimeEntry::class, 'person_id');
    }

    public function latestTimeEntry(): HasOne
    {
        return $this->hasOne(JibbleTimeEntry::class, 'person_id')
            ->orderByDesc('time')
            ->orderByDesc('created_at');
    }

    public function isOnline(): bool
    {
        $entry = $this->relationLoaded('latestTimeEntry')
            ? $this->latestTimeEntry
            : $this->latestTimeEntry()->first();

        if (! $entry) {
            return false;
        }

        if (Str::lower((string) $entry->type) !== 'clockin') {
            return false;
        }

        if (! empty($entry->next_entry_id)) {
            return false;
        }

        if ($entry->time && Carbon::parse($entry->time)->lt(now()->subHours(24))) {
            return false;
        }

        return true;
    }

    public function deleteWithData(): void
    {
        DB::transaction(function (): void {
            $this->purgeRelatedRecords();
            $this->forceDelete();
        });
    }

    protected function purgeRelatedRecords(): void
    {
        $this->purgePersonRelation(JibbleTimeEntry::class);
        $this->purgePersonRelation(JibbleTimesheet::class);
        $this->purgePersonRelation(JibbleTimesheetSummary::class);
    }

    protected function purgePersonRelation(string $model): void
    {
        $personId = $this->getKey();
        $remoteId = $this->jibble_id;

        $query = $model::query()->where(function ($builder) use ($personId, $remoteId): void {
            $builder->where('person_id', $personId);

            if ($remoteId) {
                $builder->orWhere('jibble_person_id', $remoteId);
            }
        });

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $query->withTrashed()->forceDelete();

            return;
        }

        $query->delete();
    }
}
