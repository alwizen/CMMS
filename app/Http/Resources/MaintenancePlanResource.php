<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenancePlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'maintenance_classification' => $this->maintenance_classification,
            'interval' => $this->interval,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment->id,
                'tag_number' => $this->equipment->tag_number,
                'name' => $this->equipment->name,
                'area' => $this->whenLoaded('equipment.area', fn () => [
                    'id' => $this->equipment->area->id,
                    'name' => $this->equipment->area->name,
                    'company' => $this->whenLoaded('equipment.area.company', fn () => [
                        'id' => $this->equipment->area->company->id,
                        'name' => $this->equipment->area->company->name,
                    ]),
                ]),
            ]),
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'technician_coordinator' => $this->whenLoaded('technicianCoordinator', fn () => [
                'id' => $this->technicianCoordinator->id,
                'name' => $this->technicianCoordinator->name,
            ]),
            'plan_activities' => MaintenancePlanActivityResource::collection($this->whenLoaded('planActivities')),
            'schedules' => MaintenanceScheduleResource::collection($this->whenLoaded('maintenanceSchedules')),
        ];
    }
}
