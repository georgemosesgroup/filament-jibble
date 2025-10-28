<?php

namespace Gpos\FilamentJibble\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public function tenant(): BelongsTo
    {
        $tenantModel = $this->resolveTenantModelClass();

        return $this->belongsTo($tenantModel, 'tenant_id');
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
}
