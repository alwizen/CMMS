<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceRequest extends Model
{
    protected static function booted(): void
    {
        static::creating(function (MaintenanceRequest $record) {
            $today = now()->format('Ymd');
            $lastNumber = static::where('request_number', 'like', "MR-{$today}-%")
                ->pluck('request_number')
                ->map(fn ($rn) => (int) substr($rn, -4))
                ->max() ?? 0;
            $record->request_number = 'MR-' . $today . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
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
}

