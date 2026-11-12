<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HousekeepingPlanTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'note' => $this->note,
            'photo_before' => $this->photo_before ?? [],
            'photo_after' => $this->photo_after ?? [],
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'facility' => $this->whenLoaded('facility', fn () => $this->facility ? [
                'id' => $this->facility->id,
                'name' => $this->facility->name,
                'code' => $this->facility->code,
            ] : null),
            'master_task' => $this->whenLoaded('masterTask', fn () => $this->masterTask ? [
                'id' => $this->masterTask->id,
                'name' => $this->masterTask->name,
            ] : null),
        ];
    }
}
