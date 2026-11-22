<?php

namespace App\Http\Resources;

use App\Models\WorkOrderActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isQualitative = $this->usesQualitativeResult();

        return [
            'id' => $this->id,
            'name' => $this->displayName(),
            'reference' => $this->reference,
            'pre_inspection' => $this->pre_inspection,
            'follow_up' => $this->follow_up,
            'final_result' => $this->final_result,
            'unit' => $this->unit,
            'executed' => $this->executed,
            'note' => $this->note,
            'photos' => $this->photos ?? [],
            'answer_type' => $isQualitative ? 'qualitative' : 'quantitative',
            'is_qualitative' => $isQualitative,
            // Android memakai daftar ini untuk dropdown Pre Inspection & Final Result.
            'result_options' => $isQualitative
                ? collect(
                    WorkOrderActivity::qualitativeOptions($this->pre_inspection)
                    + WorkOrderActivity::qualitativeOptions($this->final_result),
                )
                    ->map(fn (string $label, string $value): array => [
                        'value' => $value,
                        'label' => $label,
                    ])
                    ->values()
                    ->all()
                : [],
            'activity' => $this->whenLoaded('activity', fn () => $this->activity ? [
                'id' => $this->activity->id,
                'name' => $this->activity->name,
                'type' => $this->activity->type,
                'reference' => $this->activity->reference,
                'answer_type' => $this->activity->answer_type,
                'optimum' => $this->activity->optimum,
                'minimum' => $this->activity->minimum,
                'maximum' => $this->activity->maximum,
                'unit' => $this->activity->unit,
            ] : null),
        ];
    }
}
