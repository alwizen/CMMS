<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    protected $fillable = [
        'equipment_type_id',
        'name',
        'type',
        'maintenance_classification',
        'interval',
        'answer_type',
        'reference',
        'optimum',
        'minimum',
        'maximum',
        'unit',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class);
    }

    public function maintenancePlanActivities(): HasMany
    {
        return $this->hasMany(MaintenancePlanActivity::class);
    }

    public function workOrderActivities(): HasMany
    {
        return $this->hasMany(WorkOrderActivity::class);
    }
}

