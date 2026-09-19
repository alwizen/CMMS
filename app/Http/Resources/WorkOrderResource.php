<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_order_number' => $this->work_order_number,
            'classification' => $this->classification,
            'interval' => $this->interval,
            'start_at' => $this->start_at?->toIso8601String(),
            'finish_at' => $this->finish_at?->toIso8601String(),
            'note' => $this->note,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment->id,
                'tag_number' => $this->equipment->tag_number,
                'name' => $this->equipment->name,
                'equipment_type' => $this->equipment->equipment_type,
                'area' => $this->whenLoaded('equipment.area', fn () => [
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
            'maintenance_plan' => $this->whenLoaded('maintenancePlan', fn () => [
                'id' => $this->maintenancePlan->id,
                'maintenance_classification' => $this->maintenancePlan->maintenance_classification,
                'interval' => $this->maintenancePlan->interval,
            ]),
            'maintenance_schedule' => $this->whenLoaded('maintenanceSchedule', fn () => [
                'id' => $this->maintenanceSchedule->id,
                'scheduled_date' => $this->maintenanceSchedule->scheduled_date?->format('Y-m-d'),
                'status' => $this->maintenanceSchedule->status,
            ]),
            'maintenance_request' => $this->whenLoaded('maintenanceRequest', fn () => [
                'id' => $this->maintenanceRequest->id,
                'request_number' => $this->maintenanceRequest->request_number,
                'status' => $this->maintenanceRequest->status,
            ]),
            'activities' => WorkOrderActivityResource::collection($this->whenLoaded('workOrderActivities')),
            'workers' => WorkOrderWorkerResource::collection($this->whenLoaded('workOrderWorkers')),
        ];
    }
}
