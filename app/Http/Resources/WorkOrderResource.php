<?php

namespace App\Http\Resources;

use App\Models\WorkOrder;
use App\Support\UserVisibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isLocked = ! UserVisibility::hasFullAccess($user) && $this->isLockedFor($user);
        $isExecutableStatus = in_array($this->status, [
            WorkOrder::STATUSES['Pending'],
            WorkOrder::STATUSES['In Progress'],
        ], true);
        $isInProgress = $this->status === WorkOrder::STATUSES['In Progress'];

        return [
            'id' => $this->id,
            'work_order_number' => $this->work_order_number,
            'classification' => $this->classification,
            'fungsi' => $this->fungsi,
            'is_unclaimed' => $this->isUnclaimed(),
            'is_locked' => $isLocked,
            'locked_by' => $isLocked
                ? [
                    'id' => $this->technician_coordinator_id,
                    'name' => $this->lockedByName(),
                ]
                : null,
            'lock_message' => $isLocked ? $this->lockMessage() : null,
            'interval' => $this->interval,
            'start_at' => $this->start_at?->toIso8601String(),
            'finish_at' => $this->finish_at?->toIso8601String(),
            'expired_at' => $this->expired_at?->toIso8601String(),
            'is_expired' => $this->isExpired(),
            'note' => $this->note,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'approval_notes' => $this->approval_notes,
            'is_awaiting_review' => $this->isAwaitingReview(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'approved_by' => $this->whenLoaded('approvedBy', fn () => $this->approvedBy ? [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->name,
            ] : null),
            'can_execute' => $this->isExecutable() && $isExecutableStatus && ! $isLocked,
            // Checklist hanya bisa diisi setelah pekerjaan dimulai (In Progress).
            'can_fill_checklist' => $this->isExecutable() && $isInProgress && ! $isLocked,
            'created_at' => $this->created_at?->toIso8601String(),
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment->id,
                'tag_number' => $this->equipment->tag_number,
                'name' => $this->equipment->description,
                'equipment_type' => $this->equipment->equipmentType?->name,
                'area' => $this->when($this->equipment?->relationLoaded('area'), fn () => [
                    'id' => $this->equipment->area->id,
                    'name' => $this->equipment->area->name,
                ]),
            ]),
            'issued_by' => $this->whenLoaded('issuedBy', fn () => [
                'id' => $this->issuedBy->id,
                'name' => $this->issuedBy->name,
            ]),
            'technician_coordinator' => $this->whenLoaded('technicianCoordinator', fn () => [
                'id' => $this->technicianCoordinator->id,
                'name' => $this->technicianCoordinator->name,
            ]),
            'maintenance_request' => $this->whenLoaded('maintenanceRequest', fn () => [
                'id' => $this->maintenanceRequest->id,
                'request_number' => $this->maintenanceRequest->request_number,
                'status' => $this->maintenanceRequest->status,
            ]),
            'activities' => WorkOrderActivityResource::collection($this->whenLoaded('workOrderActivities')),
            'workers' => WorkOrderWorkerResource::collection($this->whenLoaded('workOrderWorkers')),
            'findings' => WorkOrderFindingResource::collection($this->whenLoaded('findings')),
        ];
    }
}
