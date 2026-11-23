<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HousekeepingPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $progress = $this->progress_percent;

        return [
            'id' => $this->id,
            'plan_number' => $this->plan_number,
            'plan_date' => $this->plan_date?->format('Y-m-d'),
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'approval_notes' => $this->approval_notes,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'note' => $this->note,
            'progress_percent' => $progress,
            'sla_days' => $this->area?->sla_days,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'sla_status' => $this->sla_status,
            'is_overdue' => $this->is_overdue,
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
                'sla_days' => $this->area->sla_days,
                'kpi_class' => $this->area->kpi_class,
            ]),
            'supervisor' => $this->whenLoaded('area', fn () => $this->area->supervisor?->name),
            'manpower_count' => $this->when(
                $this->relationLoaded('manpowers'),
                fn () => $this->manpowers->count(),
            ),
            'user_count' => $this->when(
                $this->relationLoaded('users'),
                fn () => $this->users->count(),
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
