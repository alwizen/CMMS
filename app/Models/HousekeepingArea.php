<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HousekeepingArea extends Model
{
    protected $fillable = [
        'company_id',
        'supervisor_id',
        'code',
        'name',
        'description',
        'polygon',
        'area_size',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'polygon' => 'array',
        'area_size' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(HousekeepingFacility::class);
    }

    public function manpowers(): HasMany
    {
        return $this->hasMany(HousekeepingManpower::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(HousekeepingPlan::class);
    }
}
