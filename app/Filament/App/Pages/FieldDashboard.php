<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\FieldHousekeepingStatsWidget;
use App\Filament\App\Widgets\FieldRecentHousekeepingPlansWidget;
use App\Filament\App\Widgets\FieldRecentWorkOrdersWidget;
use App\Filament\App\Widgets\FieldStatsOverviewWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class FieldDashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('View:FieldDashboard');
    }

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

    public function getWidgets(): array
    {
        $user = auth()->user();

        $widgets = [];

        if ($user?->can('ViewAny:WorkOrder')) {
            $widgets[] = FieldStatsOverviewWidget::class;
            $widgets[] = FieldRecentWorkOrdersWidget::class;
        }

        if ($user?->can('ViewAny:HousekeepingPlan')) {
            $widgets[] = FieldHousekeepingStatsWidget::class;
            $widgets[] = FieldRecentHousekeepingPlansWidget::class;
        }

        return $widgets;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
