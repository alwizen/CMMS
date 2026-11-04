<?php

namespace App\Filament\Resources\MaintenanceHistories\Pages;

use App\Filament\Resources\MaintenanceHistories\MaintenanceHistoryResource;
use Filament\Actions\Action;
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
            Action::make('pdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn () => route('maintenance-histories.pdf', request()->only(['from', 'until', 'equipment_id', 'area_id'])))
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}
