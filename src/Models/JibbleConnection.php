<?php

namespace Gpos\FilamentJibble\Models;

use Gpos\FilamentJibble\Casts\EncryptedArray;
use Gpos\FilamentJibble\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

class JibbleConnection extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'jibble_connections';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'organization_uuid',
        'organization_name',
        'client_id',
        'client_secret',
        'api_token',
        'settings',
    ];

    protected $casts = [
        'api_token' => 'encrypted',
        'client_secret' => 'encrypted',
        'settings' => EncryptedArray::class,
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function getMaskedClientSecretAttribute(): ?string
    {
        $secret = $this->client_secret;

        if (blank($secret)) {
            return null;
        }

        return substr($secret, 0, 4).'••••'.substr($secret, -2);
    }

    public function getSettings(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->settings ?? [], $key, $default);
    }

    public function setSettings(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        Arr::set($settings, $key, $value);

        $this->settings = $settings;
    }

    public function getDefaultProjectId(): ?string
    {
        $id = $this->getSettings('default_project_id');

        return filled($id) ? (string) $id : null;
    }

    public function getDefaultGroupId(): ?string
    {
        $id = $this->getSettings('default_group_id');

        return filled($id) ? (string) $id : null;
    }

    public function hasProjectFilter(): bool
    {
        return filled($this->getDefaultProjectId());
    }

    public function hasGroupFilter(): bool
    {
        return filled($this->getDefaultGroupId());
    }

    public function user(): BelongsTo
    {
        $userModel = (string) (config('filament-jibble.user_model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User');

        return $this->belongsTo($userModel, 'user_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(JibbleLocation::class, 'connection_id');
    }
}
