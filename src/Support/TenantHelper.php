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
}
