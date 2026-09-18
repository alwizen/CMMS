<?php

namespace App\Filament\Resources\MeterLogs\Pages;

use App\Filament\Resources\MeterLogs\MeterLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMeterLog extends EditRecord
{
    protected static string $resource = MeterLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
