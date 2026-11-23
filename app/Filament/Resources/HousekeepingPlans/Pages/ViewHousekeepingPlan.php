<?php

namespace App\Filament\Resources\HousekeepingPlans\Pages;

use App\Filament\Resources\HousekeepingPlans\Actions\ApproveHousekeepingPlanAction;
use App\Filament\Resources\HousekeepingPlans\HousekeepingPlanResource;
use App\Filament\Resources\HousekeepingPlans\Schemas\HousekeepingPlanInfolist;
use App\Models\HousekeepingPlan;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewHousekeepingPlan extends ViewRecord
{
    protected static string $resource = HousekeepingPlanResource::class;

    public function resolveRecord(int|string $key): HousekeepingPlan
    {
        return HousekeepingPlan::query()
            ->with([
                'area.company',
                'area.supervisor',
                'createdBy',
                'users',
                'tasks' => fn ($query) => $query->orderBy('id'),
                'tasks.facility',
            ])
            ->findOrFail($key);
    }

    public function infolist(Schema $schema): Schema
    {
        return HousekeepingPlanInfolist::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            ApproveHousekeepingPlanAction::approve(),
            ApproveHousekeepingPlanAction::reject(),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
