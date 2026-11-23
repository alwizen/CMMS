<?php

namespace App\Filament\App\Pages;

use App\Filament\Actions\PrintDailyReportAction;
use App\Models\HousekeepingPlanTask;
use App\Support\HousekeepingVisibility;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HousekeepingReport extends \App\Filament\Pages\HousekeepingReport
{
    protected static ?string $navigationLabel = 'Laporan';

    protected static ?string $title = 'Laporan Pekerjaan Housekeeping';

    protected static string|UnitEnum|null $navigationGroup = 'Housekeeping';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('View:HousekeepingReport');
    }

    protected function getHeaderActions(): array
    {
        return [
            PrintDailyReportAction::make('housekeeping', 'app.daily-report.pdf'),
        ];
    }

    protected function reportQuery(): Builder
    {
        return HousekeepingVisibility::scopeTasks(
            HousekeepingPlanTask::query()
                ->with(['plan.area.company', 'plan.area.supervisor', 'facility']),
            auth()->user(),
        );
    }
}
