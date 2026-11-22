<?php

namespace App\Observers;

use App\Models\MaintenanceHistory;
use App\Models\MaintenanceRequest;
use App\Models\WorkOrder;
use App\Notifications\WorkOrderPendingReviewNotification;
use App\Support\DocumentNumber;
use App\Support\Notify;
use Illuminate\Support\Facades\Notification;

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
        if ($workOrder->maintenance_request_id) {
            MaintenanceRequest::query()
                ->whereKey($workOrder->maintenance_request_id)
                ->where('status', MaintenanceRequest::STATUSES['Open'])
                ->update(['status' => MaintenanceRequest::STATUSES['Assigned']]);
        }

        // WO yang langsung dibuat berstatus Completed tetap perlu review SPV MOS.
        if (strcasecmp((string) $workOrder->status, WorkOrder::STATUSES['Completed']) === 0) {
            $this->registerActingExecutor($workOrder);
            $workOrder->finishExecutor(
                $workOrder->technician_coordinator_id ?? auth()->id(),
            );
            $this->markPendingReview($workOrder);

            return;
        }

        // WO yang dibuat langsung berjalan (mis. impor) tetap catat pelaksana.
        if (strcasecmp((string) $workOrder->status, WorkOrder::STATUSES['In Progress']) === 0) {
            $this->registerActingExecutor($workOrder);
        }
    }

    public function updated(WorkOrder $workOrder): void
    {
        if ($workOrder->wasChanged('status')) {
            $this->claimByStartingUser($workOrder);

            $status = strtolower(trim((string) $workOrder->status));

            // Pekerjaan dimulai: catat pelaksana pada relasi Teknisi Pelaksana.
            if ($status === strtolower(WorkOrder::STATUSES['In Progress'])) {
                $this->registerActingExecutor($workOrder);
                $workOrder->resumeExecutors();
            }

            // Selesai dikerjakan teknisi: masuk antrean review SPV MOS.
            if ($status === strtolower(WorkOrder::STATUSES['Completed'])) {
                $this->registerActingExecutor($workOrder);
                $workOrder->finishExecutor(
                    $workOrder->technician_coordinator_id ?? auth()->id(),
                );
                $this->markPendingReview($workOrder);
            }

            // WO yang sudah disetujui lalu dibuka kembali perlu direview ulang.
            if (
                $status === strtolower(WorkOrder::STATUSES['In Progress'])
                && $workOrder->isApproved()
            ) {
                $workOrder->forceFill([
                    'approval_status' => WorkOrder::APPROVAL_PENDING,
                    'approval_notes' => null,
                    'approved_by' => null,
                    'approved_at' => null,
                ])->saveQuietly();
            }

            $this->syncRequestStatus($workOrder);
        }

        // Review disetujui: WO baru dianggap tuntas (history & request selesai).
        if (
            $workOrder->wasChanged('approval_status')
            && $workOrder->approval_status === WorkOrder::APPROVAL_APPROVED
        ) {
            $this->createMaintenanceHistory($workOrder);
            $this->syncRequestStatus($workOrder);
        }
    }

    /**
     * Catat user yang sedang bertindak sebagai pelaksana WO.
     *
     * - Bila performed by sudah ada, pakai user tersebut.
     * - Bila belum (mis. supervisor menandai selesai), pakai user yang login
     *   dengan role Supervisor agar jejak pelaksana tetap tercatat.
     */
    protected function registerActingExecutor(WorkOrder $workOrder): void
    {
        $coordinatorId = $workOrder->technician_coordinator_id;

        if ($coordinatorId !== null) {
            $workOrder->registerExecutor($coordinatorId);

            return;
        }

        $actorId = auth()->id();

        if ($actorId === null) {
            return;
        }

        $isSupervisor = auth()->user()?->hasAnyRole(['spv_mos', 'super_admin']) ?? false;

        $workOrder->registerExecutor((int) $actorId, $isSupervisor ? 'Supervisor' : 'Teknisi');
    }

    /**
     * Tandai WO menunggu review dan beri tahu SPV MOS.
     */
    protected function markPendingReview(WorkOrder $workOrder): void
    {
        $workOrder->forceFill([
            'approval_status' => WorkOrder::APPROVAL_PENDING,
            'approval_notes' => null,
            'approved_by' => null,
            'approved_at' => null,
        ])->saveQuietly();

        $recipients = Notify::supervisors();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new WorkOrderPendingReviewNotification($workOrder));
    }

    /**
     * WO milik fungsi (shared): user pertama yang memulai pengerjaan
     * dicatat sebagai performed by (technician_coordinator_id).
     */
    protected function claimByStartingUser(WorkOrder $workOrder): void
    {
        if (! $workOrder->isUnclaimed()) {
            return;
        }

        $status = strtolower(trim((string) $workOrder->status));

        if ($status !== 'in progress') {
            return;
        }

        $userId = auth()->id();

        if (blank($userId)) {
            return;
        }

        $workOrder->claimBy((int) $userId);
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

        if ($status === 'in progress') {
            $request->update(['status' => MaintenanceRequest::STATUSES['In Progress']]);

            return;
        }

        $siblings = WorkOrder::query()
            ->where('maintenance_request_id', $request->id)
            ->get(['id', 'status', 'approval_status']);

        if ($siblings->isEmpty()) {
            return;
        }

        // WO Completed baru dianggap tuntas setelah disetujui SPV MOS.
        $isClosed = fn (WorkOrder $wo): bool => $wo->status === WorkOrder::STATUSES['Cancelled']
            || $wo->isFinalized();

        $allClosed = $siblings->every($isClosed);
        $anyFinalized = $siblings->contains(fn (WorkOrder $wo): bool => $wo->isFinalized());

        if ($allClosed && $anyFinalized) {
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

        $performedBy = $workOrder->technician_coordinator_id
            ?? $workOrder->workOrderWorkers->first()?->user_id
            ?? $workOrder->issued_by;

        MaintenanceHistory::updateOrCreate(
            ['work_order_id' => $workOrder->id],
            [
                'equipment_id' => $workOrder->equipment_id,
                'work_order_number' => $workOrder->work_order_number,
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
