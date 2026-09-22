<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'operation_status' => $this->operation_status,
            'description' => $this->description,
            'damage_date' => $this->damage_date?->format('Y-m-d'),
            'damage_time' => $this->damage_time?->format('H:i'),
            'equipment_condition' => $this->equipment_condition,
            'impact' => $this->impact,
            'early_action' => $this->early_action,
            'status' => $this->status,
            'photo' => $this->photo,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment->id,
                'tag_number' => $this->equipment->tag_number,
                'name' => $this->equipment->name,
                'equipment_type' => $this->equipment->equipmentType?->name,
                'area' => $this->whenLoaded('equipment.area', fn () => [
                    'id' => $this->equipment->area->id,
                    'name' => $this->equipment->area->name,
                ]),
            ]),
            'reported_by' => $this->whenLoaded('reportedBy', fn () => [
                'id' => $this->reportedBy->id,
                'name' => $this->reportedBy->name,
            ]),
            'work_orders' => WorkOrderResource::collection($this->whenLoaded('workOrders')),
        ];
    }
}
