<?php

namespace App\Observers;

use App\Models\MaintenanceHistory;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSchedule;
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

    public function created(WorkOrder $workOrder): void
    {
        if (! $workOrder->maintenance_request_id) {
            return;
        }

        MaintenanceRequest::query()
            ->whereKey($workOrder->maintenance_request_id)
            ->where('status', MaintenanceRequest::STATUSES['Open'])
            ->update(['status' => MaintenanceRequest::STATUSES['Assigned']]);
    }

    public function updated(WorkOrder $workOrder): void
    {
        if ($workOrder->wasChanged('status')) {
            $this->syncRequestStatus($workOrder);
        }

        if ($workOrder->wasChanged('status') && strcasecmp((string) $workOrder->status, 'Completed') === 0) {
            $this->createMaintenanceHistory($workOrder);

            if ($workOrder->maintenance_schedule_id) {
                MaintenanceSchedule::query()
                    ->whereKey($workOrder->maintenance_schedule_id)
                    ->update(['status' => MaintenanceSchedule::STATUSES['Completed']]);
            }
        }
    }

    /**
     * Sinkronkan status Maintenance Request mengikuti progres Work Order.
     *
     * - WO mulai dikerjakan  -> request "In Progress"
     * - semua WO tuntas/batal -> request "Completed" (minimal satu Completed)
     * - semua WO batal         -> request dibiarkan, perlu keputusan manual
     */
    protected function syncRequestStatus(WorkOrder $workOrder): void
    {
        if (! $workOrder->maintenance_request_id) {
            return;
        }

        $request = MaintenanceRequest::query()->find($workOrder->maintenance_request_id);

        if (! $request) {
            return;
        }

        $protected = [
            MaintenanceRequest::STATUSES['Rejected'],
            MaintenanceRequest::STATUSES['Completed'],
        ];

        if (in_array($request->status, $protected, true)) {
            return;
        }

        $status = strtolower(trim((string) $workOrder->status));

        if ($status === 'in progress' || $status === 'on hold') {
            $request->update(['status' => MaintenanceRequest::STATUSES['In Progress']]);

            return;
        }

        $siblings = WorkOrder::query()
            ->where('maintenance_request_id', $request->id)
            ->get(['id', 'status']);

        if ($siblings->isEmpty()) {
            return;
        }

        $closed = [WorkOrder::STATUSES['Completed'], WorkOrder::STATUSES['Cancelled']];

        $allClosed = $siblings->every(fn (WorkOrder $wo): bool => in_array($wo->status, $closed, true));
        $anyCompleted = $siblings->contains(fn (WorkOrder $wo): bool => $wo->status === WorkOrder::STATUSES['Completed']);

        if ($allClosed && $anyCompleted) {
            $request->update(['status' => MaintenanceRequest::STATUSES['Completed']]);
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
