<?php

namespace App\Filament\Pages;

use App\Models\MaintenanceSchedule;
use App\Models\MaintenancePlan;
use App\Models\WorkOrder;
use Filament\Pages\Page;

class MaintenanceCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Kalender Maintenance';

    protected static string|UnitEnum|null $navigationGroup = 'Maintenance';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.maintenance-calendar';

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getCalendarEvents(): array
    {
        $events = [];

        WorkOrder::with('equipment')
            ->whereNotNull('start_at')
            ->get()
            ->each(function (WorkOrder $wo) use (&$events) {
                $color = match ($wo->status) {
                    'Completed' => '#16a34a',
                    'In Progress' => '#2563eb',
                    'On Hold' => '#d97706',
                    'Cancelled' => '#dc2626',
                    default => '#6b7280',
                };

                $events[] = [
                    'id' => 'wo-' . $wo->id,
                    'title' => $wo->work_order_number . ' — ' . $wo->equipment->tag_number,
                    'start' => $wo->start_at->toDateTimeString(),
                    'end' => $wo->finish_at?->toDateTimeString(),
                    'color' => $color,
                    'extendedProps' => [
                        'type' => 'work_order',
                        'status' => $wo->status,
                        'equipment' => $wo->equipment->name,
                        'classification' => $wo->classification,
                    ],
                ];
            });

        MaintenancePlan::with('equipment')
            ->where('status', 'Active')
            ->get()
            ->each(function (MaintenancePlan $mp) use (&$events) {
                $events[] = [
                    'id' => 'plan-' . $mp->id,
                    'title' => 'Plan: ' . $mp->equipment->tag_number,
                    'start' => $mp->start_date->toDateString(),
                    'end' => $mp->end_date->toDateString(),
                    'color' => '#7c3aed',
                    'display' => 'background',
                    'extendedProps' => [
                        'type' => 'plan',
                        'status' => $mp->status,
                        'equipment' => $mp->equipment->name,
                        'classification' => $mp->maintenance_classification,
                        'interval' => $mp->interval,
                    ],
                ];
            });

        MaintenanceSchedule::with('equipment')
            ->get()
            ->each(function (MaintenanceSchedule $ms) use (&$events) {
                $color = match ($ms->status) {
                    'Completed' => '#16a34a',
                    'In Progress' => '#2563eb',
                    'Scheduled' => '#d97706',
                    'Delayed' => '#ea580c',
                    'Rescheduled' => '#7c3aed',
                    'Cancelled' => '#dc2626',
                    default => '#6b7280',
                };

                $events[] = [
                    'id' => 'schedule-' . $ms->id,
                    'title' => $ms->equipment->tag_number . ' — ' . $ms->status,
                    'start' => $ms->scheduled_date->toDateString(),
                    'color' => $color,
                    'extendedProps' => [
                        'type' => 'schedule',
                        'status' => $ms->status,
                        'equipment' => $ms->equipment->name,
                        'description' => $ms->description,
                    ],
                ];
            });

        return $events;
    }
}
