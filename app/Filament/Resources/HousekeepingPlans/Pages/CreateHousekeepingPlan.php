<?php

namespace App\Filament\Resources\HousekeepingPlans\Pages;

use App\Filament\Resources\HousekeepingPlans\HousekeepingPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHousekeepingPlan extends CreateRecord
{
    protected static string $resource = HousekeepingPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
