<?php

namespace App\Filament\Pages;

use App\Filament\Widgets;
use Filament\Forms;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getTitle(): string|Htmlable
    {
        return 'Dashboard';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Dashboard';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Selamat datang, '.(auth()->user()?->name ?? 'Pengguna');
    }

    public function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->schema([
                    Forms\Components\Select::make('dateRange')
                        ->label('Periode')
                        ->options([
                            'all' => 'Semua Waktu',
                            'today' => 'Hari Ini',
                            'yesterday' => 'Kemarin',
                            'week' => '7 Hari Terakhir',
                            'month' => '1 Bulan Terakhir',
                            'custom' => 'Pilih Range',
                        ])
                        ->default('all')
                        ->live()
                        ->reactive(),
                    Forms\Components\DatePicker::make('dateFrom')
                        ->label('Dari Tanggal')
                        ->visible(fn ($get) => $get('dateRange') === 'custom'),
                    Forms\Components\DatePicker::make('dateTo')
                        ->label('Sampai Tanggal')
                        ->visible(fn ($get) => $get('dateRange') === 'custom'),
                ]),
        ];
    }

    public function getWidgets(): array
    {
        return [
            // Widgets\StatsOverviewWidget::class,
            Widgets\CompanyAreaMapWidget::class,
            Widgets\DueScheduleWidget::class,
            // Widgets\WorkOrderClassificationChart::class,
            Widgets\WorkOrderStatusChart::class,
            Widgets\MaintenanceTrendChart::class,
            Widgets\RecentWorkOrdersWidget::class,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\StatsOverviewWidget::class,
        ];
    }
}
