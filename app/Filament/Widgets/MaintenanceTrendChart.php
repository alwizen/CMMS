<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersByDate;
use App\Models\MaintenanceHistory;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MaintenanceTrendChart extends ChartWidget
{
    use FiltersByDate;

    protected ?string $heading = 'Trend Maintenance (6 Bulan)';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('M Y'));
        }

        $monthExpression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', maintenance_date)",
            default => "DATE_FORMAT(maintenance_date, '%Y-%m')",
        };

        $data = MaintenanceHistory::selectRaw("{$monthExpression} as month, COUNT(*) as count")
            ->where('maintenance_date', '>=', now()->subMonths(6))
            ->groupByRaw($monthExpression)
            ->pluck('count', 'month')
            ->toArray();

        $labels = $months->map(fn ($m) => Carbon::parse($m)->format('Y-m'))->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Maintenance',
                    'data' => array_map(fn ($key) => $data[$key] ?? 0, $labels),
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
