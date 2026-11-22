<?php

namespace App\Filament\App\Resources\MaintenanceRequests\Pages;

use App\Filament\App\Resources\MaintenanceRequests\MaintenanceRequestResource;
use App\Models\MaintenanceRequest;
use Filament\Resources\Pages\EditRecord;

class EditMaintenanceRequest extends EditRecord
{
    protected static string $resource = MaintenanceRequestResource::class;

    public function resolveRecord(int|string $key): MaintenanceRequest
    {
        return MaintenanceRequestResource::getEloquentQuery()->findOrFail($key);
    }

    protected function getRedirectUrl(): string
    {
        return MaintenanceRequestResource::getUrl('view', ['record' => $this->record]);
    }
}
