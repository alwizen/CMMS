<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Livewire\Attributes\On;

trait FiltersByDate
{
    use InteractsWithPageFilters;

    /**
     * Filter efektif dari event dashboard (menimpa pageFilters bila ada).
     *
     * @var array<string, mixed>|null
     */
    public ?array $appliedFilters = null;

    /**
     * Sinkronkan filter ketika halaman dashboard mengubah periode.
     */
    #[On('dashboard-filters-updated')]
    public function syncDashboardFilters(?array $filters = null): void
    {
        $this->appliedFilters = $filters;
    }

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

    /**
     * ID user yang dipilih pada filter global dashboard (null = semua user).
     */
    private function userFilterId(): ?int
    {
        $value = $this->dateFilterValue('user_id');

        return filled($value) ? (int) $value : null;
    }

    /**
     * Terapkan filter user. Default kolom: technician_coordinator_id.
     */
    private function applyUserFilter($query, string $column = 'technician_coordinator_id'): void
    {
        $userId = $this->userFilterId();

        if ($userId) {
            $query->where($column, $userId);
        }
    }

    /**
     * Fungsi yang dipilih pada filter global dashboard (null = semua fungsi).
     */
    private function fungsiFilter(): ?string
    {
        $value = $this->dateFilterValue('fungsi');

        return filled($value) ? (string) $value : null;
    }

    /**
     * Terapkan filter fungsi lewat relasi koordinator WO.
     */
    private function applyFungsiFilter($query, string $relation = 'technicianCoordinator'): void
    {
        $fungsi = $this->fungsiFilter();

        if ($fungsi) {
            $query->whereHas($relation, fn ($q) => $q->where('fungsi', $fungsi));
        }
    }

    private function dateFilterRange(): string
    {
        return (string) ($this->dateFilterValue('dateRange') ?? 'all');
    }

    private function dateFilterValue(string $key): mixed
    {
        // Filter hanya dari page/applied filters. Tidak membaca query string
        // agar filter lama tidak ikut terpakai setelah refresh/navigasi.
        $filters = $this->appliedFilters ?? $this->pageFilters;

        if (is_array($filters)) {
            return $filters[$key] ?? null;
        }

        return null;
    }
}
