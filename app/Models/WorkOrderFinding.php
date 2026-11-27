<?php

namespace App\Models;

use App\Support\DocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Temuan dari hasil checklist work order.
 *
 * Temuan dibuat otomatis oleh WorkOrderFindingSync saat hasil inspeksi
 * bermasalah, lalu dapat diajukan menjadi maintenance request perbaikan.
 */
class WorkOrderFinding extends Model
{
    public const STATUSES = [
        'Open' => 'Open',
        'Requested' => 'Sudah Diajukan',
        'Resolved' => 'Selesai',
        'Dismissed' => 'Diabaikan',
    ];

    public const SEVERITIES = [
        'Low' => 'Low',
        'Medium' => 'Medium',
        'High' => 'High',
        'Critical' => 'Critical',
    ];

    public const ANSWER_TYPES = [
        'qualitative' => 'Qualitative',
        'quantitative' => 'Quantitative',
    ];

    protected $fillable = [
        'finding_number',
        'work_order_id',
        'work_order_activity_id',
        'equipment_id',
        'activity_id',
        'answer_type',
        'result',
        'severity',
        'description',
        'status',
        'maintenance_request_id',
        'resolution_work_order_id',
        'reported_by',
        'photos',
        'notes',
        'resolved_at',
    ];

    protected $casts = [
        'photos' => 'array',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (WorkOrderFinding $finding): void {
            if (blank($finding->finding_number)) {
                $finding->finding_number = DocumentNumber::generate(
                    'finding',
                    static::class,
                    'finding_number',
                );
            }
        });
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function workOrderActivity(): BelongsTo
    {
        return $this->belongsTo(WorkOrderActivity::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function resolutionWorkOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'resolution_work_order_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUSES['Open'];
    }

    public function isRequested(): bool
    {
        return $this->status === self::STATUSES['Requested'];
    }

    /**
     * Nama item checklist asal temuan (fallback ke master Activity).
     */
    public function displayName(): string
    {
        return $this->workOrderActivity?->displayName()
            ?? $this->activity?->name
            ?? ('Temuan #'.$this->id);
    }
}
