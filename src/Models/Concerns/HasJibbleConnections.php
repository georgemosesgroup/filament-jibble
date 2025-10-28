<?php

namespace Gpos\FilamentJibble\Models\Concerns;

use Gpos\FilamentJibble\Models\JibbleConnection;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasJibbleConnections
{
    public function jibbleConnections(): HasMany
    {
        return $this->hasMany(JibbleConnection::class, $this->getJibbleConnectionsForeignKey());
    }

    protected function getJibbleConnectionsForeignKey(): string
    {
        return property_exists($this, 'jibbleConnectionsForeignKey')
            ? $this->jibbleConnectionsForeignKey
            : 'tenant_id';
    }
}
