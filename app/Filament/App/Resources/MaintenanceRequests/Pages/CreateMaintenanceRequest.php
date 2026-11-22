<?php

namespace App\Filament\App\Resources\MaintenanceRequests\Pages;

use App\Filament\App\Resources\MaintenanceRequests\MaintenanceRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaintenanceRequest extends CreateRecord
{
    protected static string $resource = MaintenanceRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return MaintenanceRequestResource::getUrl('view', ['record' => $this->record]);
    }
}
