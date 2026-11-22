<?php

namespace App\Filament\App\Resources\MaintenanceRequests\Pages;

use App\Filament\App\Resources\MaintenanceRequests\MaintenanceRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceRequests extends ListRecords
{
    protected static string $resource = MaintenanceRequestResource::class;

    public function getTitle(): string
    {
        return 'Request';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Request')
                ->visible(fn (): bool => MaintenanceRequestResource::canCreate()),
        ];
    }
}
