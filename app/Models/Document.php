<?php

namespace App\Models;

use App\Support\DocumentNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    public const TYPES = [
        'certification' => 'Certification',
        'pnid' => 'P&ID',
        'user_manual' => 'User Manual',
        'drawing' => 'Drawing',
        'procedure' => 'Procedure / SOP',
        'inspection_report' => 'Inspection Report',
        'other' => 'Other',
    ];

    public const EXPIRABLE_TYPES = [
        'certification',
        'inspection_report',
    ];

    public const EXPIRY_STATUSES = [
        'valid' => 'Valid',
        'expiring_soon' => 'Expiring Soon',
        'expired' => 'Expired',
        'no_expiry' => 'No Expiry',
    ];

    public const EXPIRING_SOON_DAYS = 30;

    protected static function booted(): void
    {
        static::creating(function (Document $record) {
            if (blank($record->document_number)) {
                $record->document_number = DocumentNumber::generate(
                    'document',
                    static::class,
                    'document_number',
                );
            }

            if (blank($record->uploaded_by) && auth()->check()) {
                $record->uploaded_by = auth()->id();
            }
        });
    }

    protected $fillable = [
        'document_number',
        'title',
        'document_type',
        'equipment_id',
        'revision',
        'issuer',
        'issue_date',
        'expiry_date',
        'file_path',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeOfExpiryStatus(Builder $query, string $status): Builder
    {
        $soon = today()->addDays(self::EXPIRING_SOON_DAYS);

        return match ($status) {
            'expired' => $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<', today()),
            'expiring_soon' => $query->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', today())
                ->whereDate('expiry_date', '<=', $soon),
            'valid' => $query->whereNotNull('expiry_date')->whereDate('expiry_date', '>', $soon),
            'no_expiry' => $query->whereNull('expiry_date'),
            default => $query,
        };
    }

    public function expiryStatus(): string
    {
        if ($this->expiry_date === null) {
            return 'no_expiry';
        }

        if ($this->expiry_date->lt(today())) {
            return 'expired';
        }

        if ($this->expiry_date->lte(today()->addDays(self::EXPIRING_SOON_DAYS))) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    public function expiryStatusLabel(): string
    {
        return self::EXPIRY_STATUSES[$this->expiryStatus()];
    }
}
