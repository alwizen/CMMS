<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    /**
     * Daftar type activity yang tersedia.
     */
    public const TYPES = [
        'maintenance' => 'Maintenance',
        'inspection' => 'Inspection',
        'testing' => 'Testing',
        'visual_check' => 'Visual Check',
        'measurement' => 'Measurement',
        'lubrication' => 'Lubrication',
        'cleaning' => 'Cleaning',
        'calibration' => 'Calibration',
        'replacement' => 'Replacement',
    ];

    /**
     * Daftar maintenance classification yang tersedia.
     */
    public const CLASSIFICATIONS = [
        'preventive' => 'Preventive',
        'corrective' => 'Corrective',
        'predictive' => 'Predictive',
        'condition_based' => 'Condition-Based',
    ];

    /**
     * Daftar interval yang tersedia.
     */
    public const INTERVALS = [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'semi_annual' => 'Semi-Annual',
        'yearly' => 'Yearly',
    ];

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
