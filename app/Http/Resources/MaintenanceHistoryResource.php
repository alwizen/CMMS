<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_order_number' => $this->work_order_number,
            'classification' => $this->classification,
            'description' => $this->description,
            'maintenance_date' => $this->maintenance_date?->toIso8601String(),
            'status' => $this->status,
            'duration_minutes' => $this->duration_minutes,
            'findings' => $this->findings,
            'actions_taken' => $this->actions_taken,
            'notes' => $this->notes,
            'performed_by' => $this->whenLoaded('performedBy', fn () => $this->performedBy ? [
                'id' => $this->performedBy->id,
                'name' => $this->performedBy->name,
            ] : null),
            'checklist' => $this->whenLoaded('workOrder', fn () => $this->workOrder?->workOrderActivities
                ?->map(fn ($activity): array => [
                    'id' => $activity->id,
                    'name' => $activity->displayName(),
                    'executed' => (bool) $activity->executed,
                    'unit' => $activity->unit,
                    'pre_inspection' => $activity->pre_inspection,
                    'final_result' => $activity->final_result,
                    'note' => $activity->note,
                ])
                ->values() ?? []),
        ];
    }
}
