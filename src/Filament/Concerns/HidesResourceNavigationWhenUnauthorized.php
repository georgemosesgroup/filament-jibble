<?php

namespace Gpos\FilamentJibble\Filament\Concerns;

trait HidesResourceNavigationWhenUnauthorized
{
    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}

