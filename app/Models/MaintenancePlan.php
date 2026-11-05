<?php

namespace App\Models;

use App\Support\DocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenancePlan extends Model
{
    protected static function booted(): void
    {
        static::creating(function (MaintenancePlan $plan): void {
            if (blank($plan->plan_number)) {
                $plan->plan_number = DocumentNumber::generate(
                    'maintenance_plan',
                    static::class,
                    'plan_number',
                );
            }
        });

        static::saving(function (MaintenancePlan $plan): void {
            if (filled($plan->interval)) {
                $plan->interval = strtolower(trim((string) $plan->interval));
            }
        });
    }

    protected $fillable = [
        'plan_number',
        'equipment_id',
        'maintenance_classification',
        'interval',
        'start_date',
        'end_date',
        'created_by',
        'technician_coordinator_id',
        'description',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function technicianCoordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_coordinator_id');
    }

    public function planActivities(): HasMany
    {
        return $this->hasMany(MaintenancePlanActivity::class);
    }

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
