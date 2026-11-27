<?php

namespace App\Models;

use App\Support\WorkOrderFindingSync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderActivity extends Model
{
    /**
     * Pilihan hasil untuk checklist kualitatif (Pre Inspection & Final Result).
     *
     * @var array<string, string>
     */
    public const QUALITATIVE_OPTIONS = [
        'Good' => 'Good – Kondisi baik dan berfungsi normal',
        'Fair' => 'Fair – Kondisi cukup baik, ada sedikit kerusakan atau penurunan fungsi',
        'Poor' => 'Poor – Kondisi buruk, perlu perbaikan',
        'Not Working' => 'Not Working – Tidak berfungsi atau rusak total',
        'Under Maintenance' => 'Under Maintenance – Sedang dalam perawatan atau perbaikan',
    ];

    protected $fillable = [
        'work_order_id',
        'activity_id',
        'name',
        'reference',
        'pre_inspection',
        'follow_up',
        'final_result',
        'unit',
        'executed',
        'executed_at',
        'note',
        'photos',
    ];

    protected $casts = [
        'executed' => 'boolean',
        'executed_at' => 'datetime',
        'photos' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (WorkOrderActivity $activity): void {
            if ($activity->executed && blank($activity->executed_at)) {
                $activity->executed_at = now();
            }

            if (! $activity->executed) {
                $activity->executed_at = null;
            }
        });

        // Hasil bermasalah otomatis menjadi temuan; temuan Open diabaikan
        // kembali bila hasil diperbaiki.
        static::saved(function (WorkOrderActivity $activity): void {
            WorkOrderFindingSync::sync($activity);
        });

        static::deleting(function (WorkOrderActivity $activity): void {
            WorkOrderFinding::query()
                ->where('work_order_activity_id', $activity->id)
                ->where('status', WorkOrderFinding::STATUSES['Open'])
                ->delete();
        });
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Nama item checklist: dari master Activity, atau nama manual bila diketik sendiri.
     */
    public function displayName(): string
    {
        return $this->name
            ?? $this->activity?->name
            ?? ('Aktivitas #'.$this->id);
    }

    /**
     * Apakah checklist ini memakai jawaban kualitatif.
     *
     * Item manual (tanpa master Activity) dianggap kualitatif karena tidak
     * punya satuan/nilai optimum.
     */
    public static function isQualitative(?int $activityId): bool
    {
        if ($activityId === null) {
            return true;
        }

        return Activity::query()
            ->whereKey($activityId)
            ->value('answer_type') !== 'quantitative';
    }

    /**
     * Apakah record ini memakai jawaban kualitatif.
     */
    public function usesQualitativeResult(): bool
    {
        return static::isQualitative($this->activity_id);
    }

    /**
     * Opsi dropdown kualitatif, tetap menyertakan nilai lama agar data
     * existing (mis. "ok") tidak hilang saat diedit.
     *
     * @return array<string, string>
     */
    public static function qualitativeOptions(?string $currentValue = null): array
    {
        $options = self::QUALITATIVE_OPTIONS;

        if (filled($currentValue) && ! array_key_exists($currentValue, $options)) {
            $options[$currentValue] = $currentValue;
        }

        return $options;
    }
}
