<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HousekeepingFacility extends Model
{
    protected $fillable = [
        'housekeeping_area_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(HousekeepingArea::class, 'housekeeping_area_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(HousekeepingTask::class);
    }

    public function planTasks(): HasMany
    {
        return $this->hasMany(HousekeepingPlanTask::class);
    }
}
