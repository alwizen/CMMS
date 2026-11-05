<?php

namespace App\Observers;

use App\Models\MaintenanceHistory;
use App\Models\WorkOrder;
use App\Support\DocumentNumber;

class WorkOrderObserver
{
    public function creating(WorkOrder $workOrder): void
    {
        if (! $workOrder->work_order_number) {
            $workOrder->work_order_number = DocumentNumber::generate(
                'work_order',
                WorkOrder::class,
                'work_order_number',
            );
        }
    }

    public function created(WorkOrder $workOrder): void {}

    public function updated(WorkOrder $workOrder): void
    {
        if ($workOrder->wasChanged('status') && strcasecmp((string) $workOrder->status, 'Completed') === 0) {
            $this->createMaintenanceHistory($workOrder);
        }
    }

    public function deleted(WorkOrder $workOrder): void {}

    public function restored(WorkOrder $workOrder): void {}

    public function forceDeleted(WorkOrder $workOrder): void {}

    private function createMaintenanceHistory(WorkOrder $workOrder): void
    {
        $workOrder->load(['workOrderActivities.activity', 'workOrderWorkers.user']);

        $duration = null;
        if ($workOrder->start_at && $workOrder->finish_at) {
            $duration = $workOrder->start_at->diffInMinutes($workOrder->finish_at);
        }

        $findings = $workOrder->workOrderActivities
            ->whereNotNull('final_result')
            ->pluck('final_result')
            ->implode("\n");

        $actionsTaken = $workOrder->workOrderActivities
            ->whereNotNull('note')
            ->pluck('note')
            ->implode("\n");

        $performedBy = $workOrder->workOrderWorkers->first()?->user_id
            ?? $workOrder->issued_by;

        MaintenanceHistory::updateOrCreate(
            ['work_order_id' => $workOrder->id],
            [
                'equipment_id' => $workOrder->equipment_id,
                'work_order_number' => $workOrder->work_order_number,
                'maintenance_type' => $workOrder->classification,
                'classification' => $workOrder->classification,
                'description' => $workOrder->note ?? $workOrder->work_order_number,
                'maintenance_date' => $workOrder->finish_at ?? now(),
                'performed_by' => $performedBy,
                'findings' => $findings ?: null,
                'actions_taken' => $actionsTaken ?: null,
                'status' => 'Completed',
                'duration_minutes' => $duration,
                'notes' => $workOrder->note,
            ],
        );
    }
}
