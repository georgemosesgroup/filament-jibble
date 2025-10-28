<?php

namespace Gpos\FilamentJibble\Support;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class TenantHelper
{
    public static function current(): ?Model
    {
        $manager = Filament::getFacadeRoot();

        if (! $manager) {
            return null;
        }

        if (method_exists($manager, 'getTenant')) {
            return Filament::getTenant();
        }

        if (method_exists($manager, 'getCurrentTenant')) {
            return $manager->getCurrentTenant();
        }

        return null;
    }

    public static function tenantColumn(): string
    {
        return (string) config('filament-jibble.tenant_foreign_key', 'tenant_id');
    }

    /**
     * Execute the given callback while impersonating the provided tenant.
     *
     * @template TReturn
     *
     * @param  callable():TReturn  $callback
     * @return TReturn
     */
    public static function forTenant(?Model $tenant, callable $callback): mixed
    {
        $manager = Filament::getFacadeRoot();

        if (! $manager) {
            return $callback();
        }

        $original = static::current();

        $setTenant = fn (?Model $model) => method_exists($manager, 'setTenant')
            ? Filament::setTenant($model)
            : null;

        $forgetTenant = fn () => method_exists($manager, 'forgetTenant')
            ? Filament::forgetTenant()
            : null;

        if ($tenant) {
            $setTenant($tenant);
        } elseif ($original) {
            $forgetTenant();
        }

        try {
            return $callback();
        } finally {
            if ($original) {
                $setTenant($original);
            } else {
                $forgetTenant();
            }
        }
    }
}
