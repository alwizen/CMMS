<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeterLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'equipment_id' => $this->equipment_id,
            'reading_date' => $this->reading_date?->format('Y-m-d'),
            'value' => (float) $this->value,
            'recorded_by' => $this->recorded_by,
            'recorded_by_name' => $this->whenLoaded('recordedBy', fn () => $this->recordedBy?->name),
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment->id,
                'tag_number' => $this->equipment->tag_number,
                'description' => $this->equipment->description,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
