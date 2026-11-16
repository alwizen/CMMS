<?php

namespace App\Models;

use App\Support\DocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HousekeepingRequest extends Model
{
    public const STATUSES = [
        'Open' => 'Open',
        'Approved' => 'Approved',
        'Rejected' => 'Rejected',
    ];

    public const PRIORITIES = [
        'Low' => 'Low',
        'Normal' => 'Normal',
        'High' => 'High',
    ];

    protected static function booted(): void
    {
        static::creating(function (HousekeepingRequest $request): void {
            if (blank($request->request_number)) {
                $request->request_number = DocumentNumber::generate(
                    'housekeeping_request',
                    static::class,
                    'request_number',
                );
            }

            if (blank($request->requested_by) && ($userId = auth()->id())) {
                $request->requested_by = $userId;
            }
        });
    }

    protected $fillable = [
        'housekeeping_area_id',
        'housekeeping_facility_id',
        'request_number',
        'request_date',
        'request_time',
        'description',
        'priority',
        'status',
        'photo',
        'requested_by',
        'approval_notes',
        'approved_by',
        'approved_at',
        'housekeeping_plan_id',
    ];

    protected $casts = [
        'request_date' => 'date',
        'request_time' => 'datetime',
        'photo' => 'array',
        'approved_at' => 'datetime',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(HousekeepingArea::class, 'housekeeping_area_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(HousekeepingFacility::class, 'housekeeping_facility_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(HousekeepingPlan::class, 'housekeeping_plan_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUSES['Open'];
    }
}
