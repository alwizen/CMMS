<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousekeepingPlanTask extends Model
{
    public const STATUSES = [
        'Pending' => 'Pending',
        'In Progress' => 'In Progress',
        'Done' => 'Done',
    ];

    protected $fillable = [
        'housekeeping_plan_id',
        'housekeeping_task_id',
        'housekeeping_facility_id',
        'name',
        'status',
        'photo_before',
        'photo_after',
        'started_at',
        'completed_at',
        'note',
    ];

    protected $casts = [
        'photo_before' => 'array',
        'photo_after' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (HousekeepingPlanTask $task): void {
            if ($task->status === 'In Progress' && blank($task->started_at)) {
                $task->started_at = now();
            }

            if ($task->status === 'Done' && blank($task->completed_at)) {
                $task->completed_at = now();
            }
        });

        static::saved(function (HousekeepingPlanTask $task): void {
            $task->plan?->syncStatusFromTasks();
        });

        static::deleted(function (HousekeepingPlanTask $task): void {
            $task->plan?->syncStatusFromTasks();
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(HousekeepingPlan::class, 'housekeeping_plan_id');
    }

    public function masterTask(): BelongsTo
    {
        return $this->belongsTo(HousekeepingTask::class, 'housekeeping_task_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(HousekeepingFacility::class, 'housekeeping_facility_id');
    }
}
