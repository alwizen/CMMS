<?php

namespace App\Filament\Pages;

use App\Models\MaintenanceSchedule;
use App\Models\MaintenancePlan;
use App\Models\WorkOrder;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Filament\Resources\MaintenancePlans\MaintenancePlanResource;
use App\Filament\Resources\MaintenanceSchedules\MaintenanceScheduleResource;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;
use BackedEnum;

class MaintenanceCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Kalender Maintenance';

    protected static string|UnitEnum|null $navigationGroup = 'Maintenance';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.maintenance-calendar';

    public function getCalendarEvents(): array
    {
        $events = [];

        WorkOrder::with(['equipment', 'equipment.area'])
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

                $woNumber = $wo->work_order_number;
                $tag = $wo->equipment->tag_number;

                $events[] = [
                    'id' => 'wo-' . $wo->id,
                    'title' => $woNumber . ' — ' . $tag,
                    'start' => $wo->start_at->toDateTimeString(),
                    'end' => $wo->finish_at?->toDateTimeString(),
                    'color' => $color,
                    'extendedProps' => [
                        'type' => 'work_order',
                        'status' => $wo->status,
                        'equipment' => $wo->equipment->name . ' (' . $tag . ')',
                        'area' => $wo->equipment->area->name ?? '-',
                        'classification' => $wo->classification,
                        'issuedBy' => $wo->issuedBy?->name ?? '-',
                        'note' => $wo->note,
                        'recordId' => $wo->id,
                    ],
                ];
            });

        MaintenancePlan::with(['equipment', 'equipment.area'])
            ->where('status', 'Active')
            ->get()
            ->each(function (MaintenancePlan $mp) use (&$events) {
                $tag = $mp->equipment->tag_number;

                $events[] = [
                    'id' => 'plan-' . $mp->id,
                    'title' => 'Plan: ' . $tag,
                    'start' => $mp->start_date->toDateString(),
                    'end' => $mp->end_date->copy()->addDay()->toDateString(),
                    'color' => '#7c3aed',
                    'display' => 'background',
                    'extendedProps' => [
                        'type' => 'plan',
                        'status' => $mp->status,
                        'equipment' => $mp->equipment->name . ' (' . $tag . ')',
                        'area' => $mp->equipment->area->name ?? '-',
                        'classification' => $mp->maintenance_classification,
                        'interval' => $mp->interval,
                        'description' => $mp->description,
                        'createdBy' => $mp->createdBy?->name ?? '-',
                        'recordId' => $mp->id,
                    ],
                ];
            });

        MaintenanceSchedule::with(['equipment', 'equipment.area', 'maintenancePlan'])
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

                $tag = $ms->equipment->tag_number;

                $events[] = [
                    'id' => 'schedule-' . $ms->id,
                    'title' => $tag . ' — ' . $ms->status,
                    'start' => $ms->scheduled_date->toDateString(),
                    'color' => $color,
                    'extendedProps' => [
                        'type' => 'schedule',
                        'status' => $ms->status,
                        'equipment' => $ms->equipment->name . ' (' . $tag . ')',
                        'area' => $ms->equipment->area->name ?? '-',
                        'description' => $ms->description,
                        'planName' => $ms->maintenancePlan
                            ? ($ms->maintenancePlan->equipment->tag_number ?? '-') . ' — ' . $ms->maintenancePlan->interval
                            : '-',
                        'rescheduledFrom' => $ms->rescheduled_from?->format('d M Y'),
                        'recordId' => $ms->id,
                    ],
                ];
            });

        return $events;
    }
}
