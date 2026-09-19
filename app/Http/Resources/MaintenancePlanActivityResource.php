<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenancePlanActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sort_order' => $this->sort_order,
            'activity' => $this->whenLoaded('activity', fn () => [
                'id' => $this->activity->id,
                'name' => $this->activity->name,
                'type' => $this->activity->type,
                'maintenance_classification' => $this->activity->maintenance_classification,
                'interval' => $this->activity->interval,
                'reference' => $this->activity->reference,
                'optimum' => $this->activity->optimum,
                'minimum' => $this->activity->minimum,
                'maximum' => $this->activity->maximum,
                'unit' => $this->activity->unit,
            ]),
        ];
    }
}
