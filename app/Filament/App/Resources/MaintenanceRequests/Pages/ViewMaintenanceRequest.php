<?php

namespace App\Filament\App\Resources\MaintenanceRequests\Pages;

use App\Filament\App\Resources\MaintenanceRequests\MaintenanceRequestResource;
use App\Filament\Resources\MaintenanceRequests\Schemas\MaintenanceRequestInfolist;
use App\Models\MaintenanceRequest;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewMaintenanceRequest extends ViewRecord
{
    protected static string $resource = MaintenanceRequestResource::class;

    public function resolveRecord(int|string $key): MaintenanceRequest
    {
        return MaintenanceRequestResource::getEloquentQuery()
            ->with(['equipment.equipmentType', 'equipment.area', 'reportedBy', 'approvedBy', 'workOrders'])
            ->findOrFail($key);
    }

    public function infolist(Schema $schema): Schema
    {
        return MaintenanceRequestInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => MaintenanceRequestResource::canEdit($this->record)),
        ];
    }
}
