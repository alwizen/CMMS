<?php

namespace App\Models;

use App\Support\DocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HousekeepingPlan extends Model
{
    public const STATUSES = [
        'Draft' => 'Draft',
        'In Progress' => 'In Progress',
        'Completed' => 'Completed',
    ];

    protected static function booted(): void
    {
        static::creating(function (HousekeepingPlan $plan): void {
            if (blank($plan->plan_number)) {
                $plan->plan_number = DocumentNumber::generate(
                    'housekeeping_plan',
                    static::class,
                    'plan_number',
                );
            }

            if (blank($plan->created_by) && ($userId = auth()->id())) {
                $plan->created_by = $userId;
            }
        });
    }

    protected $fillable = [
        'plan_number',
        'housekeeping_area_id',
        'plan_date',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'plan_date' => 'date',
    ];

    /**
     * Hitung ulang status plan berdasarkan progres item checklist.
     * - Semua task Done (minimal 1)            -> Completed
     * - Ada task In Progress / Done sebagian   -> In Progress
     * - Selain itu                             -> Draft
     */
    public function syncStatusFromTasks(): void
    {
        $tasks = $this->tasks()->get(['status']);

        $total = $tasks->count();

        if ($total === 0) {
            $status = 'Draft';
        } else {
            $done = $tasks->where('status', 'Done')->count();

            if ($done === $total) {
                $status = 'Completed';
            } elseif ($done > 0 || $tasks->where('status', 'In Progress')->isNotEmpty()) {
                $status = 'In Progress';
            } else {
                $status = 'Draft';
            }
        }

        if ($this->status !== $status) {
            $this->status = $status;
            $this->saveQuietly();
        }
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(HousekeepingArea::class, 'housekeeping_area_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function manpowers(): BelongsToMany
    {
        return $this->belongsToMany(
            HousekeepingManpower::class,
            'housekeeping_plan_manpowers',
            'housekeeping_plan_id',
            'housekeeping_manpower_id',
        )->withPivot('note')->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(HousekeepingPlanTask::class);
    }
}
