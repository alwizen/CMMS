<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersByDate;
use App\Models\Equipment;
use Filament\Widgets\ChartWidget;

class EquipmentByLocationChart extends ChartWidget
{
    use FiltersByDate;

    protected ?string $heading = 'Equipment per Area';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Equipment::selectRaw('area_id, COUNT(*) as count')
            ->with('area')
            ->get()
            ->pluck('count', 'area.name')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Equipment',
                    'data' => array_values($data),
                    'backgroundColor' => [
                        '#3b82f6', '#10b981', '#f59e0b',
                        '#ef4444', '#8b5cf6', '#ec4899',
                        '#06b6d4', '#f97316', '#14b8a6',
                        '#6366f1',
                    ],
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
