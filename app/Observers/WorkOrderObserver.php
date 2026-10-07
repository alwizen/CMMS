<?php

namespace App\Observers;

use App\Models\MaintenanceHistory;
use App\Models\WorkOrder;

class WorkOrderObserver
{
    public function creating(WorkOrder $workOrder): void
    {
        if (!$workOrder->work_order_number) {
            $workOrder->work_order_number = $this->generateWorkOrderNumber();
        }
    }

    public function created(WorkOrder $workOrder): void
    {
    }

    public function updated(WorkOrder $workOrder): void
    {
        if ($workOrder->wasChanged('status') && $workOrder->status === 'completed') {
            $this->createMaintenanceHistory($workOrder);
        }
    }

    public function deleted(WorkOrder $workOrder): void
    {
    }

    public function restored(WorkOrder $workOrder): void
    {
    }

    public function forceDeleted(WorkOrder $workOrder): void
    {
    }

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

        MaintenanceHistory::create([
            'equipment_id'      => $workOrder->equipment_id,
            'work_order_id'     => $workOrder->id,
            'work_order_number' => $workOrder->work_order_number,
            'maintenance_type'  => $workOrder->classification,
            'classification'    => $workOrder->classification,
            'description'       => $workOrder->note ?? $workOrder->work_order_number,
            'maintenance_date'  => $workOrder->finish_at ?? now(),
            'performed_by'      => $performedBy,
            'findings'          => $findings ?: null,
            'actions_taken'     => $actionsTaken ?: null,
            'status'            => 'Completed',
            'duration_minutes'  => $duration,
            'notes'             => $workOrder->note,
        ]);
    }

    private function generateWorkOrderNumber(): string
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $date = now()->format('d');
        
        $count = WorkOrder::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereDay('created_at', $date)
            ->count() + 1;
        
        return 'WO-' . $year . $month . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
