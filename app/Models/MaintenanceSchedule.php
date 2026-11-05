<?php

namespace App\Models;

use App\Support\DocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceSchedule extends Model
{
    public const STATUSES = [
        'Scheduled' => 'Scheduled',
        'In Progress' => 'In Progress',
        'Completed' => 'Completed',
        'Delayed' => 'Delayed',
        'Rescheduled' => 'Rescheduled',
        'Cancelled' => 'Cancelled',
    ];

    protected static function booted(): void
    {
        static::creating(function (MaintenanceSchedule $schedule): void {
            if (blank($schedule->schedule_number)) {
                $schedule->schedule_number = DocumentNumber::generate(
                    'maintenance_schedule',
                    static::class,
                    'schedule_number',
                );
            }
        });
    }

    protected $fillable = [
        'schedule_number',
        'maintenance_plan_id',
        'equipment_id',
        'scheduled_date',
        'status',
        'description',
        'rescheduled_from',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'rescheduled_from' => 'date',
    ];

    public function maintenancePlan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
