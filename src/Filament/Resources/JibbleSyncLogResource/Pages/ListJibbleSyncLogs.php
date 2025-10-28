<?php

namespace Gpos\FilamentJibble\Filament\Resources\JibbleSyncLogResource\Pages;

use Gpos\FilamentJibble\Filament\Resources\JibbleSyncLogResource;
use Filament\Resources\Pages\ListRecords;

class ListJibbleSyncLogs extends ListRecords
{
    protected static string $resource = JibbleSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
