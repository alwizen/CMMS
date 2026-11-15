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
            'maintenance_type' => $this->maintenance_type,
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
        ];
    }
}
