<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersByDate;
use App\Models\WorkOrder;
use Filament\Widgets\ChartWidget;

class WorkOrderClassificationChart extends ChartWidget
{
    use FiltersByDate;

    protected ?string $heading = 'Work Order per Klasifikasi';

    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $data = WorkOrder::query()
            ->selectRaw('classification, COUNT(*) as count')
            ->tap(fn ($q) => $this->applyDateFilter($q))
            ->groupBy('classification')
            ->get();

        $colors = [
            'Preventive' => '#10b981',
            'Corrective' => '#ef4444',
        ];

        return [
            'datasets' => [
                [
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => $data->pluck('classification')
                        ->map(fn ($c) => $colors[$c] ?? '#6b7280')
                        ->toArray(),
                ],
            ],
            'labels' => $data->pluck('classification')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
