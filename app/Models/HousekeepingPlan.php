<?php

namespace App\Models;

use App\Notifications\HousekeepingPlanPendingNotification;
use App\Observers\HousekeepingPlanObserver;
use App\Support\DocumentNumber;
use App\Support\Notify;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

#[ObservedBy([HousekeepingPlanObserver::class])]
class HousekeepingPlan extends Model
{
    public const STATUSES = [
        'Draft' => 'Draft',
        'In Progress' => 'In Progress',
        'Completed' => 'Completed',
    ];

    public const APPROVAL_PENDING = 'Pending';

    public const APPROVAL_APPROVED = 'Approved';

    public const APPROVAL_REJECTED = 'Rejected';

    /**
     * Label status persetujuan SPV GOS. Laporan harian hanya bisa digenerate
     * bila seluruh plan terkait sudah `Approved`.
     */
    public const APPROVAL_STATUSES = [
        self::APPROVAL_PENDING => 'Menunggu Persetujuan',
        self::APPROVAL_APPROVED => 'Disetujui',
        self::APPROVAL_REJECTED => 'Ditolak',
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

    protected $attributes = [
        'approval_status' => self::APPROVAL_PENDING,
    ];

    protected $fillable = [
        'plan_number',
        'housekeeping_area_id',
        'plan_date',
        'status',
        'approval_status',
        'approval_notes',
        'approved_by',
        'approved_at',
        'note',
        'created_by',
    ];

    protected $casts = [
        'plan_date' => 'date',
        'approved_at' => 'datetime',
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

            // Plan selesai dikerjakan: SPV GOS perlu memverifikasi sebelum
            // laporan harian bisa digenerate.
            if ($status === self::STATUSES['Completed']) {
                $this->notifyPendingVerification();
            }
        }
    }

    /**
     * Pekerjaan berubah setelah di-review: approval dibatalkan agar SPV GOS
     * memeriksa ulang sebelum laporan harian bisa digenerate.
     */
    public function reopenVerification(): void
    {
        if (! in_array($this->approval_status, [self::APPROVAL_APPROVED, self::APPROVAL_REJECTED], true)) {
            return;
        }

        $this->forceFill([
            'approval_status' => self::APPROVAL_PENDING,
            'approval_notes' => null,
            'approved_by' => null,
            'approved_at' => null,
        ])->saveQuietly();
    }

    /**
     * Pekerjaan sudah dimulai (bukan Draft lagi).
     */
    public function hasStartedWork(): bool
    {
        return $this->status !== self::STATUSES['Draft'];
    }

    /**
     * Minta SPV GOS memeriksa hasil pekerjaan yang sudah selesai.
     */
    public function notifyPendingVerification(): void
    {
        if ($this->approval_status !== self::APPROVAL_PENDING) {
            return;
        }

        // Verifikasi adalah tugas SPV GOS, bukan penanggung jawab area
        // (petugas patra-jasa yang mengerjakan).
        $recipients = Notify::housekeepingSupervisors()
            ->filter(fn ($user): bool => (bool) $user->status)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new HousekeepingPlanPendingNotification($this));
    }

    /**
     * Pastikan setiap fasilitas yang dipakai task memiliki baris dokumentasi
     * pada plan ini. Baris yang sudah ada tidak dihapus agar foto tetap aman.
     */
    public function syncFacilities(): void
    {
        $facilityIds = $this->tasks()
            ->whereNotNull('housekeeping_facility_id')
            ->distinct()
            ->pluck('housekeeping_facility_id')
            ->all();

        $existing = $this->facilities()
            ->pluck('housekeeping_facility_id')
            ->all();

        foreach (array_diff($facilityIds, $existing) as $facilityId) {
            $planFacility = $this->facilities()->create([
                'housekeeping_facility_id' => $facilityId,
            ]);

            $this->tasks()
                ->where('housekeeping_facility_id', $facilityId)
                ->whereNull('housekeeping_plan_facility_id')
                ->update(['housekeeping_plan_facility_id' => $planFacility->id]);
        }
    }

    /**
     * Persentase item checklist yang sudah Done (0-100).
     */
    public function getProgressPercentAttribute(): int
    {
        $tasks = $this->relationLoaded('tasks')
            ? $this->tasks
            : $this->tasks()->get(['status']);

        $total = $tasks->count();

        if ($total === 0) {
            return 0;
        }

        return (int) round(($tasks->where('status', 'Done')->count() / $total) * 100);
    }

    /**
     * Waktu selesai plan (task Done terakhir).
     */
    public function getCompletedAtAttribute(): ?Carbon
    {
        $tasks = $this->relationLoaded('tasks')
            ? $this->tasks
            : $this->tasks()->get(['status', 'completed_at']);

        return $tasks->max('completed_at');
    }

    /**
     * Batas waktu pengerjaan plan: tanggal plan + SLA area (hari).
     */
    public function getDueDateAttribute(): ?Carbon
    {
        if (! $this->plan_date) {
            return null;
        }

        $slaDays = max(1, (int) ($this->area?->sla_days ?? 1));

        return $this->plan_date->copy()->addDays($slaDays);
    }

    /**
     * Status SLA plan: Completed, Overdue, At Risk, atau On Track.
     */
    public function getSlaStatusAttribute(): string
    {
        if ($this->status === 'Completed') {
            return 'Completed';
        }

        $due = $this->due_date;

        if (! $due) {
            return 'On Track';
        }

        $now = now();

        if ($now->greaterThan($due)) {
            return 'Overdue';
        }

        $slaDays = max(1, (int) ($this->area?->sla_days ?? 1));
        $remaining = ($due->getTimestamp() - $now->getTimestamp()) / 86400;
        $threshold = max(0.2, $slaDays * 0.2);

        if ($remaining <= $threshold) {
            return 'At Risk';
        }

        return 'On Track';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'Completed'
            && $this->due_date !== null
            && now()->greaterThan($this->due_date);
    }

    /**
     * Apakah plan selesai tepat waktu sesuai SLA area.
     */
    public function getCompletedWithinSlaAttribute(): ?bool
    {
        if ($this->status !== 'Completed') {
            return null;
        }

        $completedAt = $this->completed_at;
        $due = $this->due_date;

        if (! $completedAt || ! $due) {
            return null;
        }

        return $completedAt->lessThanOrEqualTo($due->copy()->endOfDay());
    }

    /**
     * Jumlah hari dari tanggal plan sampai selesai.
     */
    public function getCompletionDaysAttribute(): ?int
    {
        if ($this->status !== 'Completed') {
            return null;
        }

        $completedAt = $this->completed_at;

        if (! $completedAt) {
            return null;
        }

        return (int) $this->plan_date
            ->copy()
            ->startOfDay()
            ->diffInDays($completedAt->copy()->startOfDay());
    }

    /**
     * Plan yang masih menghalangi pembuatan plan baru untuk area yang sama.
     *
     * Plan dianggap menghalangi bila belum selesai DAN batas SLA-nya belum lewat.
     * Setelah SLA lewat (overdue), area boleh digenerate plan baru.
     */
    public function blocksNewPlan(): bool
    {
        if ($this->status === self::STATUSES['Completed']) {
            return false;
        }

        $due = $this->due_date;

        return $due !== null && now()->lessThanOrEqualTo($due);
    }

    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function isApprovalRejected(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    /**
     * Petugas housekeeping (user role patra-jasa) yang ditugaskan ke plan.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'housekeeping_plan_users',
            'housekeeping_plan_id',
            'user_id',
        )->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(HousekeepingPlanTask::class);
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(HousekeepingPlanFacility::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(HousekeepingRequest::class);
    }
}
