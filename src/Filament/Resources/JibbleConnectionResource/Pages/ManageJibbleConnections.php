<?php

namespace Gpos\FilamentJibble\Filament\Resources\JibbleConnectionResource\Pages;

use Gpos\FilamentJibble\Filament\Resources\JibbleConnectionResource;
use Gpos\FilamentJibble\Support\TenantHelper;
use Filament\Resources\Pages\ManageRecords;

class ManageJibbleConnections extends ManageRecords
{
    protected static string $resource = JibbleConnectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = TenantHelper::current();
        $column = TenantHelper::tenantColumn();
        $data[$column] = $tenant?->getKey();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tenant = TenantHelper::current();
        $column = TenantHelper::tenantColumn();
        $data[$column] ??= $tenant?->getKey();

        return $data;
    }
}
