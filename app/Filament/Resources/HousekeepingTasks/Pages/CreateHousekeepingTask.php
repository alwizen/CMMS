<?php

namespace App\Filament\Resources\HousekeepingTasks\Pages;

use App\Filament\Resources\HousekeepingTasks\HousekeepingTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHousekeepingTask extends CreateRecord
{
    protected static string $resource = HousekeepingTaskResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
