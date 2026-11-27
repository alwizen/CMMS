<?php

namespace App\Filament\Resources\MaintenanceHistories\Pages;

use App\Filament\Resources\MaintenanceHistories\MaintenanceHistoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Livewire\Attributes\Url;

class ListMaintenanceHistories extends ListRecords
{
    protected static string $resource = MaintenanceHistoryResource::class;

    #[Url]
    public ?array $tableFilters = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
