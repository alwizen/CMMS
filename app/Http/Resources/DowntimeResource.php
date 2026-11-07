<?php

namespace App\Http\Resources;

use App\Support\Downtime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DowntimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $start = $this->downtimeStart();
        $end = $this->downtimeEnd();
        $minutes = $this->downtimeMinutes();

        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'description' => $this->description,
            'status' => $this->status,
            'operation_status' => $this->operation_status,
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment->id,
                'tag_number' => $this->equipment->tag_number,
                'description' => $this->equipment->description,
                'area' => $this->equipment->area?->name,
            ]),
            'downtime' => [
                'start' => $start?->toIso8601String(),
                'end' => $end?->toIso8601String(),
                'minutes' => $minutes,
                'label' => Downtime::formatMinutes($minutes),
                'ongoing' => $this->downtimeOngoing(),
            ],
        ];
    }
}
