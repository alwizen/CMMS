<?php

namespace App\Filament\Resources\HousekeepingPlans\Pages;

use App\Filament\Resources\HousekeepingPlans\HousekeepingPlanResource;
use App\Filament\Resources\HousekeepingPlans\Schemas\HousekeepingPlanInfolist;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewHousekeepingPlan extends ViewRecord
{
    protected static string $resource = HousekeepingPlanResource::class;

    public function infolist(Schema $schema): Schema
    {
        return HousekeepingPlanInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
