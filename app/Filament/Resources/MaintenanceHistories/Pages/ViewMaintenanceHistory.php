<?php

namespace App\Filament\Resources\MaintenanceHistories\Pages;

use App\Filament\Resources\MaintenanceHistories\MaintenanceHistoryResource;
use App\Filament\Resources\MaintenanceHistories\Schemas\MaintenanceHistoryInfolist;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewMaintenanceHistory extends ViewRecord
{
    protected static string $resource = MaintenanceHistoryResource::class;

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
