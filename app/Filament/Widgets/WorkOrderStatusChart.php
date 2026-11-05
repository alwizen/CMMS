<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersByDate;
use App\Models\WorkOrder;
use Filament\Widgets\ChartWidget;

class WorkOrderStatusChart extends ChartWidget
{
    use FiltersByDate;

    protected ?string $heading = 'Backlog Work Order per Status';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $counts = WorkOrder::query()
            ->selectRaw('status, COUNT(*) as count')
            ->tap(fn ($query) => $this->applyDateFilter($query))
            ->groupBy('status')
            ->pluck('count', 'status');

        $statuses = array_values(WorkOrder::STATUSES);

        $colors = [
            'Pending' => '#f59e0b',
            'In Progress' => '#3b82f6',
            'On Hold' => '#a855f7',
            'Completed' => '#22c55e',
            'Cancelled' => '#ef4444',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Work Order',
                    'data' => array_map(fn (string $status): int => (int) ($counts[$status] ?? 0), $statuses),
                    'backgroundColor' => array_map(fn (string $status): string => $colors[$status] ?? '#6b7280', $statuses),
                ],
            ],
            'labels' => $statuses,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
