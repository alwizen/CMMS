<?php

namespace App\Filament\Resources\MeterLogs\Pages;

use App\Filament\Resources\MeterLogs\MeterLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMeterLogs extends ListRecords
{
    protected static string $resource = MeterLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
