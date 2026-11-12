<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'employee_number', 'phone', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    public function maintenanceRequestsReported(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'reported_by');
    }

    public function maintenancePlansCreated(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class, 'created_by');
    }

    public function maintenancePlansCoordinated(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class, 'technician_coordinator_id');
    }

    public function workOrdersIssued(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'issued_by');
    }

    public function workOrdersCoordinated(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'technician_coordinator_id');
    }

    public function workOrderWorkers(): HasMany
    {
        return $this->hasMany(WorkOrderWorker::class);
    }

    public function meterLogsRecorded(): HasMany
    {
        return $this->hasMany(MeterLog::class, 'recorded_by');
    }

    public function housekeepingPlansCreated(): HasMany
    {
        return $this->hasMany(HousekeepingPlan::class, 'created_by');
    }

    public function housekeepingAreasSupervised(): HasMany
    {
        return $this->hasMany(HousekeepingArea::class, 'supervisor_id');
    }
}
