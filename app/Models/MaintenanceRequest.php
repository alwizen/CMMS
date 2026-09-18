<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceRequest extends Model
{
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
    ];

    protected $casts = [
        'damage_date' => 'date',
        'damage_time' => 'datetime',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}

