<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tag_number' => $this->tag_number,
            'description' => $this->description,
            'equipment_type' => $this->equipmentType?->name,
            'equipment_type_detail' => $this->whenLoaded('equipmentType', fn () => [
                'id' => $this->equipmentType->id,
                'name' => $this->equipmentType->name,
                'responsible_user' => $this->equipmentType->responsibleUser ? [
                    'id' => $this->equipmentType->responsibleUser->id,
                    'name' => $this->equipmentType->responsibleUser->name,
                ] : null,
            ]),
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'installation_date' => $this->installation_date?->format('Y-m-d'),
            'operational_unit' => $this->operational_unit,
            'status' => $this->status,
            'criticality' => $this->criticality,
            'area' => $this->whenLoaded('area', fn () => [
                'id' => $this->area->id,
                'name' => $this->area->name,
                'code' => $this->area->code,
                'company' => $this->when($this->area?->relationLoaded('company'), fn () => [
                    'id' => $this->area->company->id,
                    'name' => $this->area->company->name,
                ]),
            ]),
        ];
    }
}
