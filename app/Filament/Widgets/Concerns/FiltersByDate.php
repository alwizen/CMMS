<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

trait FiltersByDate
{
    use InteractsWithPageFilters;

    private function dateFilterFrom(): ?Carbon
    {
        $range = $this->dateFilterRange();

        return match ($range) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            'week' => now()->subDays(7)->startOfDay(),
            'month' => now()->subMonth()->startOfDay(),
            'custom' => filled($this->dateFilterValue('dateFrom'))
                ? Carbon::parse($this->dateFilterValue('dateFrom'))->startOfDay()
                : null,
            default => null,
        };
    }

    private function dateFilterTo(): ?Carbon
    {
        $range = $this->dateFilterRange();

        return match ($range) {
            'today' => now()->endOfDay(),
            'yesterday' => now()->subDay()->endOfDay(),
            'week',
            'month' => now()->endOfDay(),
            'custom' => filled($this->dateFilterValue('dateTo'))
                ? Carbon::parse($this->dateFilterValue('dateTo'))->endOfDay()
                : now()->endOfDay(),
            default => null,
        };
    }

    private function applyDateFilter($query, string $column = 'created_at'): void
    {
        $from = $this->dateFilterFrom();
        $to = $this->dateFilterTo();

        if ($from && $to) {
            $query->whereBetween($column, [$from, $to]);
        }
    }

    private function dateFilterRange(): string
    {
        return (string) ($this->dateFilterValue('dateRange') ?? 'all');
    }

    private function dateFilterValue(string $key): mixed
    {
        $filters = $this->pageFilters ?? request()->query('filters');

        if (is_array($filters)) {
            return $filters[$key] ?? null;
        }

        return request()->query($key);
    }
}
