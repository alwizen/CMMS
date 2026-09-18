<?php

namespace App\Models;

use App\Observers\WorkOrderObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([WorkOrderObserver::class])]
class WorkOrder extends Model
{
    protected $fillable = [
        'work_order_number',
        'equipment_id',
        'maintenance_plan_id',
        'maintenance_schedule_id',
        'maintenance_request_id',
        'issued_by',
        'technician_coordinator_id',
        'classification',
        'interval',
        'start_at',
        'finish_at',
        'note',
        'status',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'finish_at' => 'datetime',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function maintenancePlan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class);
    }

    public function maintenanceSchedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class);
    }

    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function technicianCoordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_coordinator_id');
    }

    public function workOrderActivities(): HasMany
    {
        return $this->hasMany(WorkOrderActivity::class);
    }

    public function workOrderWorkers(): HasMany
    {
        return $this->hasMany(WorkOrderWorker::class);
    }
}

