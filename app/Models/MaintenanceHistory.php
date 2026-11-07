<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MaintenanceHistory extends Model
{
    protected $fillable = [
        'equipment_id',
        'work_order_id',
        'work_order_number',
        'maintenance_type',
        'classification',
        'description',
        'maintenance_date',
        'performed_by',
        'findings',
        'actions_taken',
        'status',
        'duration_minutes',
        'notes',
    ];

    protected $casts = [
        'maintenance_date' => 'datetime',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Downtime hanya berlaku untuk perawatan corrective yang berasal dari request.
     */
    public function isUnplannedDowntime(): bool
    {
        if (strcasecmp((string) $this->classification, 'Corrective') !== 0) {
            return false;
        }

        return $this->workOrder?->maintenanceRequest !== null;
    }

    public function downtimeStart(): ?CarbonInterface
    {
        if (! $this->isUnplannedDowntime()) {
            return null;
        }

        return $this->workOrder?->maintenanceRequest?->downtimeStart();
    }

    public function downtimeEnd(): ?Carbon
    {
        if (! $this->isUnplannedDowntime()) {
            return null;
        }

        $end = $this->workOrder?->finish_at ?? $this->maintenance_date;

        return $end ? Carbon::parse($end) : null;
    }

    public function downtimeMinutes(): ?int
    {
        $start = $this->downtimeStart();
        $end = $this->downtimeEnd();

        if (! $start || ! $end) {
            return null;
        }

        return max(0, (int) $start->diffInMinutes($end));
    }
}
