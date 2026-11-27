<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\WorkOrderActivity;
use App\Models\WorkOrderFinding;

/**
 * Mendeteksi hasil checklist yang bermasalah lalu menyinkronkannya menjadi
 * temuan (WorkOrderFinding).
 *
 * Aturan:
 * - Kualitatif: hasil "Poor" (Medium) atau "Not Working" (High).
 * - Kuantitatif: nilai numerik di luar rentang minimum/maximum master Activity
 *   (Medium).
 *
 * Temuan yang sudah diajukan/ditutup tidak ditimpa otomatis agar jejak
 * tindak lanjut tetap utuh.
 */
class WorkOrderFindingSync
{
    /**
     * @var array<string, string>
     */
    public const QUALITATIVE_TRIGGERS = [
        'Poor' => 'Medium',
        'Not Working' => 'High',
    ];

    public static function sync(WorkOrderActivity $activity): void
    {
        $activity->loadMissing(['activity', 'workOrder']);

        $finding = WorkOrderFinding::query()
            ->where('work_order_activity_id', $activity->id)
            ->first();

        $evaluation = self::evaluate($activity);

        if ($evaluation === null) {
            // Hasil tidak lagi bermasalah: abaikan temuan yang belum diajukan.
            if ($finding && $finding->isOpen()) {
                $finding->update(['status' => WorkOrderFinding::STATUSES['Dismissed']]);
            }

            return;
        }

        if ($finding === null) {
            WorkOrderFinding::create([
                'work_order_id' => $activity->work_order_id,
                'work_order_activity_id' => $activity->id,
                'equipment_id' => $activity->workOrder?->equipment_id,
                'activity_id' => $activity->activity_id,
                'answer_type' => $activity->usesQualitativeResult() ? 'qualitative' : 'quantitative',
                'result' => $activity->final_result,
                'severity' => $evaluation['severity'],
                'description' => $evaluation['description'],
                'status' => WorkOrderFinding::STATUSES['Open'],
                'reported_by' => $activity->workOrder?->technician_coordinator_id,
                'photos' => $activity->photos,
            ]);

            return;
        }

        // Temuan yang sudah diajukan/ditutup dibiarkan; snapshot hanya
        // diperbarui selama masih Open.
        if ($finding->isOpen()) {
            $finding->update([
                'result' => $activity->final_result,
                'severity' => $evaluation['severity'],
                'description' => $evaluation['description'],
                'photos' => $activity->photos,
            ]);
        }
    }

    /**
     * Evaluasi satu item checklist. Null berarti tidak ada temuan.
     *
     * @return array{severity: string, description: string}|null
     */
    public static function evaluate(WorkOrderActivity $activity): ?array
    {
        if ($activity->executed !== true) {
            return null;
        }

        $result = trim((string) $activity->final_result);

        if ($result === '') {
            return null;
        }

        if ($activity->usesQualitativeResult()) {
            $severity = self::QUALITATIVE_TRIGGERS[$result] ?? null;

            if ($severity === null) {
                return null;
            }

            return [
                'severity' => $severity,
                'description' => $activity->displayName().' — hasil inspeksi: '.$result
                    .($activity->note ? ' ('.$activity->note.')' : ''),
            ];
        }

        $value = self::toNumber($result);

        if ($value === null) {
            return null;
        }

        /** @var Activity|null $master */
        $master = $activity->activity;

        if (! $master) {
            return null;
        }

        $minimum = self::toNumber($master->minimum);
        $maximum = self::toNumber($master->maximum);

        $belowMin = $minimum !== null && $value < $minimum;
        $aboveMax = $maximum !== null && $value > $maximum;

        if (! $belowMin && ! $aboveMax) {
            return null;
        }

        $unit = $activity->unit ?: $master->unit;
        $range = collect([
            $minimum !== null ? 'min '.self::formatNumber($minimum) : null,
            $maximum !== null ? 'max '.self::formatNumber($maximum) : null,
        ])->filter()->implode(', ');

        return [
            'severity' => 'Medium',
            'description' => $activity->displayName().' — hasil '.$result.($unit ? ' '.$unit : '')
                .' di luar batas'.($range !== '' ? ' ('.$range.')' : '')
                .($activity->note ? ' ('.$activity->note.')' : ''),
        ];
    }

    protected static function toNumber(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = str_replace(',', '.', trim((string) $value));

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    protected static function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
