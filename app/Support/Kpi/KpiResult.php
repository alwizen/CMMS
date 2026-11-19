<?php

namespace App\Support\Kpi;

/**
 * Satu baris hasil penilaian KPI untuk satu objek (user/area) pada satu periode.
 */
class KpiResult
{
    /**
     * @param  array<string, array{label: string, value: float|int|null, unit: string, direction: string, target: float|int|null, achieved: bool|null, display: string}>  $metrics
     */
    public function __construct(
        public string $source,
        public string $objectType,
        public string $objectId,
        public string $objectName,
        public int $score,
        public int $achievedCount,
        public int $metricCount,
        public array $metrics,
        public string $periodLabel,
        public array $context = [],
    ) {}

    public function grade(): string
    {
        return match (true) {
            $this->score >= 90 => 'A',
            $this->score >= 80 => 'B',
            $this->score >= 70 => 'C',
            $this->score >= 60 => 'D',
            default => 'E',
        };
    }

    public function gradeColor(): string
    {
        return match ($this->grade()) {
            'A' => 'success',
            'B' => 'info',
            'C' => 'warning',
            default => 'danger',
        };
    }

    public function status(): string
    {
        return match (true) {
            $this->metricCount === 0 => 'Tanpa Data',
            $this->score >= 80 => 'Achieve',
            $this->score >= 60 => 'Warning',
            default => 'Below Target',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status()) {
            'Achieve' => 'success',
            'Warning' => 'warning',
            'Below Target' => 'danger',
            default => 'gray',
        };
    }
}
