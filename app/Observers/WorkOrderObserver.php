<?php

namespace App\Observers;

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
