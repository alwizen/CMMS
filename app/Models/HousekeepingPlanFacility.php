<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousekeepingPlanFacility extends Model
{
    protected $fillable = [
        'housekeeping_plan_id',
        'housekeeping_facility_id',
        'photo_before',
        'photo_after',
    ];

    protected $casts = [
        'photo_before' => 'array',
        'photo_after' => 'array',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(HousekeepingPlan::class, 'housekeeping_plan_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(HousekeepingFacility::class, 'housekeeping_facility_id');
    }
}
