<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'pre_inspection' => $this->pre_inspection,
            'follow_up' => $this->follow_up,
            'final_result' => $this->final_result,
            'unit' => $this->unit,
            'executed' => $this->executed,
            'note' => $this->note,
            'activity' => $this->whenLoaded('activity', fn () => [
                'id' => $this->activity->id,
                'name' => $this->activity->name,
                'type' => $this->activity->type,
                'reference' => $this->activity->reference,
                'optimum' => $this->activity->optimum,
                'minimum' => $this->activity->minimum,
                'maximum' => $this->activity->maximum,
                'unit' => $this->activity->unit,
            ]),
        ];
    }
}
