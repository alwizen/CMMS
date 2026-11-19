<?php

namespace App\Support\Kpi;

use Illuminate\Support\Collection;

/**
 * Titik masuk tunggal untuk perhitungan KPI.
 */
class KpiService
{
    public function __construct(
        protected MaintenanceKpiCalculator $maintenance,
        protected HousekeepingKpiCalculator $housekeeping,
    ) {}

    /**
     * @return Collection<int, KpiResult>
     */
    public function results(string $source, KpiPeriod $period, ?string $fungsi = null, ?int $userId = null): Collection
    {
        $results = match (true) {
            KpiRegistry::isHousekeeping($source) => $this->housekeeping->calculate(
                $period->startDate(),
                $period->endDate(),
                KpiRegistry::normalizeSource($source),
            ),
            default => $this->maintenance->calculate($period->startDate(), $period->endDate(), $fungsi, $userId),
        };

        return $results->map(function (KpiResult $result) use ($period): KpiResult {
            $result->periodLabel = $period->label;

            return $result;
        });
    }

    /**
     * Ringkasan satu sumber untuk kartu dashboard.
     *
     * @return array{count: int, average_score: int, achieve: int, warning: int, below: int, metrics: array<string, array{label: string, unit: string, target: float|int, weight: float|int, description: string, average: string}>}
     */
    public function summary(string $source, KpiPeriod $period, ?string $fungsi = null, ?int $userId = null): array
    {
        $results = $this->results($source, $period, $fungsi, $userId);
        $metrics = KpiRegistry::metrics($source);

        $metricSummary = [];

        foreach ($metrics as $key => $metric) {
            $values = $results
                ->map(fn (KpiResult $result) => $result->metrics[$key]['value'] ?? null)
                ->filter(fn ($value) => $value !== null);

            $metricSummary[$key] = [
                'label' => $metric['label'],
                'unit' => $metric['unit'],
                'target' => $metric['target'],
                'weight' => $metric['weight'],
                'description' => $metric['description'],
                'average' => $values->isEmpty()
                    ? '-'
                    : KpiScorer::format(round($values->avg(), 1), $metric),
            ];
        }

        return [
            'count' => $results->count(),
            'average_score' => $results->isEmpty() ? 0 : (int) round($results->avg('score')),
            'achieve' => $results->where('score', '>=', 80)->count(),
            'warning' => $results->filter(fn (KpiResult $result) => $result->score >= 60 && $result->score < 80)->count(),
            'below' => $results->where('score', '<', 60)->count(),
            'metrics' => $metricSummary,
        ];
    }
}
