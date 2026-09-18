<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceHistory extends Model
{
    protected $fillable = [
        'equipment_id',
        'work_order_id',
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
}
