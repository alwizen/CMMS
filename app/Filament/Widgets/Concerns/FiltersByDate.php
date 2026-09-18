<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;

trait FiltersByDate
{
    private function dateFilterFrom(): ?Carbon
    {
        $range = request()->query('dateRange', 'all');

        return match ($range) {
            'today'     => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            'week'      => now()->subDays(7)->startOfDay(),
            'month'     => now()->subMonth()->startOfDay(),
            'custom'    => request()->query('dateFrom')
                ? Carbon::parse(request()->query('dateFrom'))->startOfDay()
                : null,
            default     => null,
        };
    }

    private function dateFilterTo(): ?Carbon
    {
        $range = request()->query('dateRange', 'all');

        return match ($range) {
            'today'     => now()->endOfDay(),
            'yesterday' => now()->subDay()->endOfDay(),
            'week',
            'month'     => now()->endOfDay(),
            'custom'    => request()->query('dateTo')
                ? Carbon::parse(request()->query('dateTo'))->endOfDay()
                : now()->endOfDay(),
            default     => null,
        };
    }

    private function applyDateFilter($query, string $column = 'created_at'): void
    {
        $from = $this->dateFilterFrom();
        $to   = $this->dateFilterTo();

        if ($from && $to) {
            $query->whereBetween($column, [$from, $to]);
        }
    }
}
