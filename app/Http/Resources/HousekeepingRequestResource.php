<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HousekeepingRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'request_date' => $this->request_date?->format('Y-m-d'),
            'request_time' => $this->request_time?->format('H:i'),
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'photo' => $this->photo ?? [],
            'approval_notes' => $this->approval_notes,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'area' => $this->whenLoaded('area', fn () => $this->area ? [
                'id' => $this->area->id,
                'name' => $this->area->name,
                'code' => $this->area->code,
            ] : null),
            'facility' => $this->whenLoaded('facility', fn () => $this->facility ? [
                'id' => $this->facility->id,
                'name' => $this->facility->name,
                'code' => $this->facility->code,
            ] : null),
            'requested_by' => $this->whenLoaded('requestedBy', fn () => $this->requestedBy?->name),
            'approved_by' => $this->whenLoaded('approvedBy', fn () => $this->approvedBy?->name),
            'plan' => $this->whenLoaded('plan', fn () => $this->plan ? [
                'id' => $this->plan->id,
                'plan_number' => $this->plan->plan_number,
                'plan_date' => $this->plan->plan_date?->format('Y-m-d'),
                'status' => $this->plan->status,
            ] : null),
        ];
    }
}
