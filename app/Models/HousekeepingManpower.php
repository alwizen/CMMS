<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HousekeepingManpower extends Model
{
    protected $fillable = [
        'housekeeping_area_id',
        'name',
        'employee_number',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(HousekeepingArea::class, 'housekeeping_area_id');
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(
            HousekeepingPlan::class,
            'housekeeping_plan_manpowers',
            'housekeeping_manpower_id',
            'housekeeping_plan_id',
        )->withPivot('note')->withTimestamps();
    }
}
