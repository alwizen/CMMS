<?php

namespace App\Filament\Resources\MaintenanceHistories\Pages;

use App\Filament\Resources\MaintenanceHistories\MaintenanceHistoryResource;
use App\Filament\Resources\MaintenanceHistories\Schemas\MaintenanceHistoryInfolist;
use App\Models\MaintenanceHistory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewMaintenanceHistory extends ViewRecord
{
    protected static string $resource = MaintenanceHistoryResource::class;

    public function resolveRecord(int|string $key): MaintenanceHistory
    {
        return MaintenanceHistory::query()
            ->with([
                'equipment.equipmentType',
                'equipment.area',
                'performedBy',
                'workOrder.maintenanceRequest',
                'workOrder.workOrderActivities.activity',
            ])
            ->findOrFail($key);
    }

    public function infolist(Schema $schema): Schema
    {
        return MaintenanceHistoryInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
