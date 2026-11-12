<?php

namespace App\Filament\Pages;

use App\Filament\Widgets;
use BackedEnum;
use Filament\Forms;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class HousekeepingDashboard extends BaseDashboard
{
    use HasFiltersAction;

    protected static string $routePath = 'housekeeping-dashboard';

    protected static ?string $title = 'Housekeeping Dashboard';

    protected static ?string $navigationLabel = 'Housekeeping Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Dashboard';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    public function getTitle(): string|Htmlable
    {
        return 'Housekeeping Dashboard';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Housekeeping Dashboard';
    }

    // public function getSubheading(): string|Htmlable|null
    // {
    //     return 'Selamat datang, '.(auth()->user()?->name ?? 'Pengguna');
    // }

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
            Widgets\HousekeepingAreaMapWidget::class,
            Widgets\RecentHousekeepingPlansWidget::class,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\HousekeepingStatsOverviewWidget::class,
        ];
    }
}
