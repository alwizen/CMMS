<?php

namespace App\Models;

use App\Observers\MaintenanceRequestObserver;
use App\Support\DocumentNumber;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[ObservedBy([MaintenanceRequestObserver::class])]
class MaintenanceRequest extends Model
{
    public const STATUSES = [
        'Open' => 'Open',
        'Approved' => 'Approved',
        'Assigned' => 'Assigned',
        'In Progress' => 'In Progress',
        'Completed' => 'Completed',
        'Rejected' => 'Rejected',
    ];

    protected static function booted(): void
    {
        static::creating(function (MaintenanceRequest $record) {
            if (blank($record->request_number)) {
                $record->request_number = DocumentNumber::generate(
                    'maintenance_request',
                    static::class,
                    'request_number',
                );
            }
        });
    }

    protected $fillable = [
        'equipment_id',
        'request_number',
        'reported_by',
        'operation_status',
        'description',
        'damage_date',
        'damage_time',
        'equipment_condition',
        'impact',
        'early_action',
        'status',
        'photo',
        'approval_notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'damage_date' => 'date',
        'damage_time' => 'datetime',
        'photo' => 'array',
        'approved_at' => 'datetime',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    /**
     * Downtime hanya dihitung untuk gangguan tak terencana (corrective).
     */
    public function isUnplannedDowntime(): bool
    {
        if ($this->status === self::STATUSES['Rejected']) {
            return false;
        }

        $workOrders = $this->relationLoaded('workOrders')
            ? $this->workOrders
            : $this->workOrders()->get(['id', 'classification']);

        if ($workOrders->isEmpty()) {
            return true;
        }

        return $workOrders->contains(
            fn (WorkOrder $workOrder): bool => strcasecmp((string) $workOrder->classification, 'Corrective') === 0,
        );
    }

    public function downtimeStart(): ?CarbonInterface
    {
        if (! $this->damage_date || ! $this->isUnplannedDowntime()) {
            return null;
        }

        $start = $this->damage_date->copy();

        if ($this->damage_time) {
            return $start->setTime(
                $this->damage_time->hour,
                $this->damage_time->minute,
                $this->damage_time->second,
            );
        }

        return $start->startOfDay();
    }

    public function downtimeOngoing(): bool
    {
        if (! $this->isUnplannedDowntime()) {
            return false;
        }

        if (in_array($this->status, [self::STATUSES['Completed'], self::STATUSES['Rejected']], true)) {
            return false;
        }

        $workOrders = $this->relationLoaded('workOrders')
            ? $this->workOrders
            : $this->workOrders()->get(['id', 'status', 'finish_at']);

        return $workOrders
            ->where('status', '!=', WorkOrder::STATUSES['Cancelled'])
            ->whereNotNull('finish_at')
            ->isEmpty();
    }

    public function downtimeEnd(): ?CarbonInterface
    {
        if (! $this->isUnplannedDowntime()) {
            return null;
        }

        $workOrders = $this->relationLoaded('workOrders')
            ? $this->workOrders
            : $this->workOrders()->get(['id', 'status', 'finish_at']);

        $finish = $workOrders
            ->where('status', '!=', WorkOrder::STATUSES['Cancelled'])
            ->max('finish_at');

        if ($finish) {
            return $finish instanceof CarbonInterface ? $finish : Carbon::parse($finish);
        }

        if ($this->downtimeOngoing()) {
            return now();
        }

        if ($this->status === self::STATUSES['Completed']) {
            return $this->updated_at;
        }

        return null;
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
