<?php

namespace Gpos\FilamentJibble\Support;

use Closure;
use Filament\Facades\Filament;
use Gpos\FilamentJibble\Models\JibbleConnection;
use Gpos\FilamentJibble\Support\TenantHelper;

class JibbleConnectionResolver
{
    /**
     * Resolve the active connection for the current request / tenant context.
     */
    public function current(): ?JibbleConnection
    {
        $customResolver = config('filament-jibble.tenant_resolver');

        if ($customResolver instanceof Closure) {
            return $customResolver();
        }

        $tenant = TenantHelper::current();

        if ($tenant && method_exists($tenant, 'jibbleConnections')) {
            return $tenant->jibbleConnections()->first();
        }

        $user = Filament::auth()->user();

        if ($user && method_exists($user, 'jibbleConnections')) {
            return $user->jibbleConnections()->first();
        }

        return JibbleConnection::query()->first();
    }
}
