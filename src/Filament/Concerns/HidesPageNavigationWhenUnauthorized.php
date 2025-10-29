<?php

namespace Gpos\FilamentJibble\Filament\Concerns;

trait HidesPageNavigationWhenUnauthorized
{
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}

