<?php

namespace App\Support\Kpi;

use Carbon\CarbonInterface;

/**
 * Rentang periode penilaian KPI.
 */
class KpiPeriod
{
    public function __construct(
        public CarbonInterface $start,
        public CarbonInterface $end,
        public string $label,
    ) {}

    /**
     * @return array<string, string>
     */
    public static function presets(): array
    {
        return [
            'this_month' => 'Bulan Ini',
            'last_month' => 'Bulan Lalu',
            'this_quarter' => 'Kuartal Ini',
            'this_year' => 'Tahun Ini',
            'custom' => 'Pilih Rentang',
        ];
    }

    public static function fromPreset(string $preset, ?string $from = null, ?string $to = null): self
    {
        $now = now();

        return match ($preset) {
            'last_month' => new self(
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
                'Bulan Lalu',
            ),
            'this_quarter' => new self(
                $now->copy()->startOfQuarter(),
                $now->copy()->endOfQuarter(),
                'Kuartal Ini',
            ),
            'this_year' => new self(
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
                'Tahun Ini',
            ),
            'custom' => new self(
                filled($from) ? now()->parse($from)->startOfDay() : $now->copy()->startOfMonth(),
                filled($to) ? now()->parse($to)->endOfDay() : $now->copy()->endOfDay(),
                'Rentang Kustom',
            ),
            default => new self(
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
                'Bulan Ini',
            ),
        };
    }

    public function startDate(): string
    {
        return $this->start->toDateString();
    }

    public function endDate(): string
    {
        return $this->end->toDateString();
    }
}
