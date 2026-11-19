<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HousekeepingPlanFacilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'photo_before' => $this->photo_before ?? [],
            'photo_after' => $this->photo_after ?? [],
            'facility' => $this->whenLoaded('facility', fn () => $this->facility ? [
                'id' => $this->facility->id,
                'name' => $this->facility->name,
                'code' => $this->facility->code,
            ] : null),
        ];
    }
}
