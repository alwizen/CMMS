<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousekeepingPlanManpower extends Model
{
    protected $fillable = [
        'housekeeping_plan_id',
        'housekeeping_manpower_id',
        'note',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(HousekeepingPlan::class, 'housekeeping_plan_id');
    }

    public function manpower(): BelongsTo
    {
        return $this->belongsTo(HousekeepingManpower::class, 'housekeeping_manpower_id');
    }
}
