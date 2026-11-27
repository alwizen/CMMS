<?php

namespace App\Http\Resources;

use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderFindingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'finding_number' => $this->finding_number,
            'answer_type' => $this->answer_type,
            'result' => $this->result,
            'severity' => $this->severity,
            'description' => $this->description,
            'status' => $this->status,
            'notes' => $this->notes,
            'photos' => Media::urls($this->photos),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'activity_name' => $this->when(
                $this->relationLoaded('workOrderActivity') || $this->relationLoaded('activity'),
                fn () => $this->displayName(),
            ),
            'work_order' => $this->whenLoaded('workOrder', fn () => [
                'id' => $this->workOrder->id,
                'work_order_number' => $this->workOrder->work_order_number,
                'status' => $this->workOrder->status,
            ]),
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment->id,
                'tag_number' => $this->equipment->tag_number,
                'equipment_type' => $this->equipment->equipmentType?->name,
            ]),
            'maintenance_request' => $this->whenLoaded('maintenanceRequest', fn () => $this->maintenanceRequest ? [
                'id' => $this->maintenanceRequest->id,
                'request_number' => $this->maintenanceRequest->request_number,
                'status' => $this->maintenanceRequest->status,
            ] : null),
        ];
    }
}
