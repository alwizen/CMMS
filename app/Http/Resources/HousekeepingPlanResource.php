<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HousekeepingPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_number' => $this->plan_number,
            'plan_date' => $this->plan_date?->format('Y-m-d'),
            'status' => $this->status,
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
            'area' => $this->whenLoaded('area', fn () => [
                'id' => $this->area->id,
                'name' => $this->area->name,
                'code' => $this->area->code,
                'company' => $this->area->relationLoaded('company') ? [
                    'id' => $this->area->company?->id,
                    'name' => $this->area->company?->name,
                ] : null,
                'supervisor' => $this->area->relationLoaded('supervisor') ? $this->area->supervisor?->name : null,
                'area_size' => $this->area->area_size,
            ]),
            'supervisor' => $this->whenLoaded('area', fn () => $this->area->supervisor?->name),
            'manpower_count' => $this->when(
                $this->relationLoaded('manpowers'),
                fn () => $this->manpowers->count(),
            ),
            'task_count' => $this->when(
                $this->relationLoaded('tasks'),
                fn () => $this->tasks->count(),
            ),
            'task_done_count' => $this->when(
                $this->relationLoaded('tasks'),
                fn () => $this->tasks->where('status', 'Done')->count(),
            ),
        ];
    }
}
