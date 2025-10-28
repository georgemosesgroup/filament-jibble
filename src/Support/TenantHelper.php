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
}
