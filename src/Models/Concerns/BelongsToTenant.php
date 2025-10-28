<?php

namespace Gpos\FilamentJibble\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public function tenant(): BelongsTo
    {
        $tenantModel = $this->resolveTenantModelClass();

        return $this->belongsTo($tenantModel, $this->getTenantForeignKey());
    }

    protected function resolveTenantModelClass(): string
    {
        $tenantModel = config('filament-jibble.tenant_model', 'App\\Models\\Tenant');

        if (is_string($tenantModel) && class_exists($tenantModel)) {
            return $tenantModel;
        }

        $fallback = config('filament-jibble.user_model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';

        if (is_string($fallback) && class_exists($fallback)) {
            return $fallback;
        }

        return static::class;
    }

    public function getTenantForeignKey(): string
    {
        return (string) config('filament-jibble.tenant_foreign_key', 'tenant_id');
    }

    public function getTenantKey(): mixed
    {
        return $this->getAttribute($this->getTenantForeignKey());
    }

    public function setTenantKey(mixed $value): void
    {
        $this->setAttribute($this->getTenantForeignKey(), $value);
    }

    public function getFillable(): array
    {
        $fillable = parent::getFillable();
        $key = $this->getTenantForeignKey();

        if (! in_array($key, $fillable, true)) {
            $fillable[] = $key;
        }

        return $fillable;
    }
}
