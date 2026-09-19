<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'status' => $this->status,
            'description' => $this->description,
            'rescheduled_from' => $this->rescheduled_from?->format('Y-m-d'),
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment->id,
                'tag_number' => $this->equipment->tag_number,
                'name' => $this->equipment->name,
                'area' => $this->whenLoaded('equipment.area', fn () => [
                    'id' => $this->equipment->area->id,
                    'name' => $this->equipment->area->name,
                ]),
            ]),
            'maintenance_plan' => $this->whenLoaded('maintenancePlan', fn () => [
                'id' => $this->maintenancePlan->id,
                'maintenance_classification' => $this->maintenancePlan->maintenance_classification,
                'interval' => $this->maintenancePlan->interval,
                'status' => $this->maintenancePlan->status,
            ]),
            'work_orders' => WorkOrderResource::collection($this->whenLoaded('workOrders')),
        ];
    }
}
