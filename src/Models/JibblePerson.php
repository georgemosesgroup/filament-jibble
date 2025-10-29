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
use Illuminate\Support\Facades\DB;

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
