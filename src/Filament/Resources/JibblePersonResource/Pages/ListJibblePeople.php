<?php

namespace Gpos\FilamentJibble\Filament\Resources\JibblePersonResource\Pages;

use Gpos\FilamentJibble\Filament\Resources\JibblePersonResource;
use Filament\Resources\Pages\ListRecords;
use Gpos\FilamentJibble\Filament\Widgets\TimesheetHeatmap;

class ListJibblePeople extends ListRecords
{
    protected static string $resource = JibblePersonResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TimesheetHeatmap::class,
        ];
    }
}
