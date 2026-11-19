<?php

namespace App\Support\Kpi;

/**
 * Ubah nilai metrik mentah menjadi skor 0-100 dengan pembobotan & target.
 *
 * - Metrik "higher is better": skor = nilai / target * 100 (maks 100)
 * - Metrik "lower is better": skor = target / nilai * 100 (nilai 0 = 100)
 * - Metrik tanpa data (null) tidak diikutkan dan bobotnya dinormalisasi ulang.
 */
class KpiScorer
{
    /**
     * @param  array<string, float|int|null>  $values
     * @param  array<string, array<string, mixed>>  $metrics
     */
    public static function build(
        string $source,
        string $objectType,
        string $objectId,
        string $objectName,
        array $values,
        array $metrics,
        string $periodLabel = '',
    ): KpiResult {
        $weightedSum = 0.0;
        $weightTotal = 0.0;
        $achievedCount = 0;
        $metricCount = 0;
        $detail = [];

        foreach ($metrics as $key => $metric) {
            $value = $values[$key] ?? null;

            if ($value === null) {
                continue;
            }

            $metricCount++;

            $target = (float) $metric['target'];
            $achieved = self::isAchieved((float) $value, $target, $metric['direction']);

            if ($achieved) {
                $achievedCount++;
            }

            $weight = (float) $metric['weight'];
            $weightedSum += self::metricScore((float) $value, $target, $metric['direction']) * $weight;
            $weightTotal += $weight;

            $detail[$key] = [
                'label' => $metric['label'],
                'value' => $value,
                'unit' => $metric['unit'],
                'direction' => $metric['direction'],
                'target' => $target,
                'achieved' => $achieved,
                'display' => self::format($value, $metric),
            ];
        }

        $score = $weightTotal > 0 ? (int) round($weightedSum / $weightTotal) : 0;

        return new KpiResult(
            source: $source,
            objectType: $objectType,
            objectId: $objectId,
            objectName: $objectName,
            score: $score,
            achievedCount: $achievedCount,
            metricCount: $metricCount,
            metrics: $detail,
            periodLabel: $periodLabel,
        );
    }

    public static function isAchieved(float $value, float $target, string $direction): bool
    {
        return $direction === 'lower'
            ? $value <= $target
            : $value >= $target;
    }

    public static function metricScore(float $value, float $target, string $direction): float
    {
        if ($target <= 0) {
            return $direction === 'lower' ? 100.0 : 0.0;
        }

        if ($direction === 'lower') {
            return $value <= 0 ? 100.0 : min(100.0, ($target / $value) * 100);
        }

        return min(100.0, ($value / $target) * 100);
    }

    /**
     * @param  array<string, mixed>  $metric
     */
    public static function format(float|int $value, array $metric): string
    {
        $decimals = (int) ($metric['decimals'] ?? 0);

        return match ($metric['unit']) {
            '%' => number_format((float) $value, $decimals, ',', '.').'%',
            default => number_format((float) $value, $decimals, ',', '.').' '.$metric['unit'],
        };
    }
}
