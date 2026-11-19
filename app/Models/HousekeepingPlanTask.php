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
        'housekeeping_plan_facility_id',
        'name',
        'status',
        'started_at',
        'completed_at',
        'note',
    ];

    protected $casts = [
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

            if ($task->housekeeping_facility_id && $task->housekeeping_plan_id) {
                $planFacility = HousekeepingPlanFacility::firstOrCreate([
                    'housekeeping_plan_id' => $task->housekeeping_plan_id,
                    'housekeeping_facility_id' => $task->housekeeping_facility_id,
                ]);

                $task->housekeeping_plan_facility_id = $planFacility->id;
            }
        });

        static::saved(function (HousekeepingPlanTask $task): void {
            // Relasi plan bisa sudah ter-cache sebelum approval berubah, jadi
            // ambil ulang agar pembatalan approval tidak terlewat.
            $task->unsetRelation('plan');
            $task->plan?->reopenVerification();
            $task->plan?->syncStatusFromTasks();
            $task->plan?->syncFacilities();
        });

        static::deleted(function (HousekeepingPlanTask $task): void {
            $task->unsetRelation('plan');
            $task->plan?->reopenVerification();
            $task->plan?->syncStatusFromTasks();
            $task->plan?->syncFacilities();
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

    /**
     * Baris dokumentasi fasilitas pada plan yang sama.
     */
    public function planFacility(): BelongsTo
    {
        return $this->belongsTo(HousekeepingPlanFacility::class, 'housekeeping_plan_facility_id');
    }
}
