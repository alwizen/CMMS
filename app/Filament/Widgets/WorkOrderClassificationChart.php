<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersByDate;
use App\Models\WorkOrder;
use Filament\Widgets\ChartWidget;

class WorkOrderClassificationChart extends ChartWidget
{
    use FiltersByDate;

    protected ?string $heading = 'Work Order per Status';

    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $data = WorkOrder::query()
            ->selectRaw('status, COUNT(*) as count')
            ->tap(fn ($q) => $this->applyDateFilter($q))
            ->groupBy('status')
            ->get();

        $colors = [
            'Pending' => '#f59e0b',
            'In Progress' => '#3b82f6',
            'Completed' => '#10b981',
            'On Hold' => '#8b5cf6',
            'Cancelled' => '#ef4444',
            'Closed' => '#6b7280',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Work Orders',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => $data->pluck('status')
                        ->map(fn ($s) => $colors[$s] ?? '#6b7280')
                        ->toArray(),
                ],
            ],
            'labels' => $data->pluck('status')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
