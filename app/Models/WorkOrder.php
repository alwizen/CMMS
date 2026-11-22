<?php

namespace App\Models;

use App\Observers\WorkOrderObserver;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([WorkOrderObserver::class])]
class WorkOrder extends Model
{
    public const STATUSES = [
        'Pending' => 'Pending',
        'In Progress' => 'In Progress',
        'Completed' => 'Completed',
        'Cancelled' => 'Cancelled',
    ];

    public const CLASSIFICATIONS = [
        'Preventive' => 'Preventive',
        'Corrective' => 'Corrective',
    ];

    public const APPROVAL_PENDING = 'Pending';

    public const APPROVAL_APPROVED = 'Approved';

    public const APPROVAL_REJECTED = 'Rejected';

    /**
     * Label review SPV MOS setelah teknisi menyelesaikan WO.
     */
    public const APPROVAL_STATUSES = [
        self::APPROVAL_PENDING => 'Menunggu Review',
        self::APPROVAL_APPROVED => 'Disetujui',
        self::APPROVAL_REJECTED => 'Ditolak',
    ];

    protected $attributes = [
        'approval_status' => self::APPROVAL_PENDING,
    ];

    protected $fillable = [
        'work_order_number',
        'equipment_id',
        'maintenance_request_id',
        'issued_by',
        'technician_coordinator_id',
        'fungsi',
        'classification',
        'interval',
        'start_at',
        'finish_at',
        'expired_days',
        'expired_at',
        'note',
        'status',
        'approval_status',
        'approval_notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'finish_at' => 'datetime',
        'expired_days' => 'integer',
        'expired_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Apakah WO melewati batas masa berlaku (belum selesai & sudah expired).
     */
    public function isExpired(): bool
    {
        if (! $this->expired_at) {
            return false;
        }

        $closed = [
            self::STATUSES['Completed'],
            self::STATUSES['Cancelled'],
        ];

        return ! in_array($this->status, $closed, true) && $this->expired_at->isPast();
    }

    /**
     * Apakah WO sudah boleh dieksekusi (tanggal mulai sudah tiba/hari ini).
     */
    public function isExecutable(): bool
    {
        if ($this->start_at === null) {
            return true;
        }

        return $this->start_at->lte(now()->endOfDay());
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
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

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * WO selesai dikerjakan teknisi dan menunggu review SPV MOS.
     */
    public function isAwaitingReview(): bool
    {
        return $this->status === self::STATUSES['Completed']
            && $this->approval_status !== self::APPROVAL_APPROVED;
    }

    /**
     * WO sudah disetujui SPV MOS.
     */
    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    /**
     * Review terakhir ditolak; WO perlu diperbaiki teknisi.
     */
    public function isReviewRejected(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    /**
     * WO benar-benar tuntas: selesai dikerjakan dan sudah disetujui SPV MOS.
     */
    public function isFinalized(): bool
    {
        return $this->status === self::STATUSES['Completed'] && $this->isApproved();
    }

    /**
     * Apakah WO belum diambil / dikerjakan siapa pun.
     */
    public function isUnclaimed(): bool
    {
        return $this->technician_coordinator_id === null;
    }

    /**
     * Apakah WO sudah diambil oleh user tertentu (performed by).
     */
    public function isClaimedBy(?User $user): bool
    {
        return $user !== null
            && $this->technician_coordinator_id !== null
            && $this->technician_coordinator_id === $user->id;
    }

    /**
     * Apakah WO sedang dikerjakan user lain sehingga user ini hanya boleh melihat.
     *
     * Kunci berlaku selama pekerjaan aktif (In Progress). WO yang sudah
     * selesai/dibatalkan tidak lagi dikunci agar riwayat tetap terbuka.
     */
    public function isLockedFor(?User $user): bool
    {
        if ($user === null || $this->technician_coordinator_id === null) {
            return false;
        }

        if ($this->technician_coordinator_id === $user->id) {
            return false;
        }

        return ! in_array($this->status, [
            self::STATUSES['Completed'],
            self::STATUSES['Cancelled'],
        ], true);
    }

    /**
     * Nama user yang sedang mengerjakan WO (null bila belum diambil).
     */
    public function lockedByName(): ?string
    {
        if ($this->technician_coordinator_id === null) {
            return null;
        }

        $this->loadMissing('technicianCoordinator');

        return $this->technicianCoordinator?->name;
    }

    /**
     * Pesan standar ketika WO sedang dikerjakan user lain.
     */
    public function lockMessage(): string
    {
        $name = $this->lockedByName();

        return $name
            ? 'Work order sedang dikerjakan oleh '.$name.'. Anda hanya dapat melihat.'
            : 'Work order sedang dikerjakan oleh user lain. Anda hanya dapat melihat.';
    }

    /**
     * Ambil WO untuk user tertentu (performed by) bila belum ada pemilik.
     *
     * Klaim dilakukan atomik agar dua user yang menekan "Mulai" bersamaan
     * tidak sama-sama menjadi pemilik.
     *
     * @return bool `true` bila user ini pemilik WO setelah klaim.
     */
    public function claimBy(?int $userId): bool
    {
        if ($userId === null) {
            return false;
        }

        if ($this->technician_coordinator_id !== null) {
            return $this->technician_coordinator_id === $userId;
        }

        $claimed = static::query()
            ->whereKey($this->getKey())
            ->whereNull('technician_coordinator_id')
            ->update(['technician_coordinator_id' => $userId]);

        if ($claimed === 0) {
            $this->refresh();

            return $this->technician_coordinator_id === $userId;
        }

        $this->setAttribute('technician_coordinator_id', $userId);
        $this->syncOriginalAttribute('technician_coordinator_id');
        $this->unsetRelation('technicianCoordinator');

        // Performed by otomatis tercatat sebagai teknisi pelaksana.
        $this->registerExecutor($userId);

        return true;
    }

    public function workOrderActivities(): HasMany
    {
        return $this->hasMany(WorkOrderActivity::class);
    }

    public function workOrderWorkers(): HasMany
    {
        return $this->hasMany(WorkOrderWorker::class);
    }

    /**
     * Catat petugas pelaksana WO (idempotent per user).
     *
     * Dipakai otomatis saat user mulai mengerjakan WO (performed by) agar
     * daftar "Teknisi Pelaksana" terisi tanpa input manual.
     */
    public function registerExecutor(?int $userId, ?string $role = 'Teknisi', ?CarbonInterface $startedAt = null): ?WorkOrderWorker
    {
        if ($userId === null) {
            return null;
        }

        $worker = $this->workOrderWorkers()->firstOrCreate(
            ['user_id' => $userId],
            [
                'role' => $role,
                'started_at' => $startedAt ?? now(),
            ],
        );

        if (! $worker->wasRecentlyCreated && $worker->started_at === null) {
            $worker->forceFill(['started_at' => $startedAt ?? now()])->saveQuietly();
        }

        return $worker;
    }

    /**
     * Tandai petugas selesai mengerjakan WO.
     *
     * Bila baris pelaksana belum ada (mis. WO lama yang selesai sebelum fitur
     * ini ada), baris dibuat otomatis agar jejak pelaksana tidak hilang.
     */
    public function finishExecutor(?int $userId = null, ?CarbonInterface $finishedAt = null): void
    {
        $userId ??= $this->technician_coordinator_id;

        if ($userId === null) {
            return;
        }

        $worker = $this->workOrderWorkers()->firstOrCreate(
            ['user_id' => $userId],
            [
                'role' => 'Teknisi',
                'started_at' => $this->start_at ?? $this->created_at ?? now(),
            ],
        );

        if ($worker->finished_at === null) {
            $worker->forceFill(['finished_at' => $finishedAt ?? $this->finish_at ?? now()])->saveQuietly();
        }
    }

    /**
     * Pekerjaan dibuka kembali (mis. review ditolak): bersihkan waktu selesai
     * pelaksana agar durasi dihitung ulang saat dikerjakan lagi.
     */
    public function resumeExecutors(): void
    {
        $this->workOrderWorkers()
            ->whereNotNull('finished_at')
            ->update(['finished_at' => null]);
    }
}
