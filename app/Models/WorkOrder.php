<?php

namespace App\Models;

use App\Observers\WorkOrderObserver;
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
        'On Hold' => 'On Hold',
        'Completed' => 'Completed',
        'Cancelled' => 'Cancelled',
    ];

    public const CLASSIFICATIONS = [
        'Preventive' => 'Preventive',
        'Corrective' => 'Corrective',
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
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'finish_at' => 'datetime',
        'expired_days' => 'integer',
        'expired_at' => 'datetime',
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

    /**
     * Apakah WO belum diambil / dikerjakan siapa pun.
     */
    public function isUnclaimed(): bool
    {
        return $this->technician_coordinator_id === null;
    }

    /**
     * Ambil WO untuk user tertentu (performed by) bila belum ada pemilik.
     */
    public function claimBy(?int $userId): void
    {
        if ($this->technician_coordinator_id !== null || $userId === null) {
            return;
        }

        $this->forceFill(['technician_coordinator_id' => $userId])->saveQuietly();
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
