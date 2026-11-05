<?php

namespace App\Models;

use App\Support\DocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceRequest extends Model
{
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
}
