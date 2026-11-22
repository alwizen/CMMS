<?php

namespace App\Filament\App\Resources\HousekeepingRequests\Pages;

use App\Filament\App\Resources\HousekeepingRequests\HousekeepingRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHousekeepingRequests extends ListRecords
{
    protected static string $resource = HousekeepingRequestResource::class;

    public function getTitle(): string
    {
        return 'Request Housekeeping';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Request')
                ->visible(fn (): bool => HousekeepingRequestResource::canCreate()),
        ];
    }
}
