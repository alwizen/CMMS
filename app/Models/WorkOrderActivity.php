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
        'note',
    ];

    protected $casts = [
        'executed' => 'boolean',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}

