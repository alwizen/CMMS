<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderActivity extends Model
{
    protected $fillable = [
        'work_order_id',
        'activity_id',
        'reference',
        'pre_inspection',
        'follow_up',
        'final_result',
        'unit',
        'executed',
        'executed_at',
        'note',
        'photos',
    ];

    protected $casts = [
        'executed' => 'boolean',
        'executed_at' => 'datetime',
        'photos' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (WorkOrderActivity $activity): void {
            if ($activity->executed && blank($activity->executed_at)) {
                $activity->executed_at = now();
            }

            if (! $activity->executed) {
                $activity->executed_at = null;
            }
        });
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
