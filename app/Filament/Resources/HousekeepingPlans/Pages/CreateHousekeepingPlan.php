<?php

namespace App\Filament\Resources\HousekeepingPlans\Pages;

use App\Filament\Resources\HousekeepingPlans\HousekeepingPlanResource;
use App\Models\HousekeepingArea;
use App\Support\HousekeepingPlanGenerator;
use App\Support\HousekeepingRole;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateHousekeepingPlan extends CreateRecord
{
    protected static string $resource = HousekeepingPlanResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return HousekeepingRole::canManagePlan(auth()->user()) && parent::canAccess($parameters);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function beforeCreate(): void
    {
        $area = HousekeepingArea::find($this->data['housekeeping_area_id'] ?? null);
        $planDate = filled($this->data['plan_date'] ?? null)
            ? Carbon::parse($this->data['plan_date'])->startOfDay()
            : null;

        if ($area === null || $planDate === null) {
            return;
        }

        $blocking = app(HousekeepingPlanGenerator::class)->blockingPlan($area, $planDate);

        if ($blocking === null) {
            return;
        }

        Notification::make()
            ->title('Tidak bisa membuat Plan Harian')
            ->body(sprintf(
                'Area %s masih punya Plan %s yang belum selesai (batas SLA %s).',
                $area->name,
                $blocking->plan_number,
                $blocking->due_date?->format('d M Y') ?? '-',
            ))
            ->danger()
            ->send();

        throw new Halt;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
