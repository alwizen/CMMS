<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HousekeepingTask extends Model
{
    protected $fillable = [
        'housekeeping_facility_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(HousekeepingFacility::class, 'housekeeping_facility_id');
    }

    public function planTasks(): HasMany
    {
        return $this->hasMany(HousekeepingPlanTask::class);
    }
}
