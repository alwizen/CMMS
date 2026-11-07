<?php

namespace App\Filament\Pages;

use App\Models\MaintenancePlan;
use App\Models\MaintenanceSchedule;
use App\Models\WorkOrder;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MaintenanceCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Kalender Maintenance';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.maintenance-calendar';

    /**
     * Rentang tampilan kalender: 3 bulan ke belakang sampai 6 bulan ke depan.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    protected function calendarWindow(): array
    {
        return [
            now()->subMonths(3)->startOfDay(),
            now()->addMonths(6)->endOfDay(),
        ];
    }

    public function getCalendarEvents(): array
    {
        $events = [];

        [$from, $until] = $this->calendarWindow();

        WorkOrder::with(['equipment.equipmentType', 'equipment.area', 'issuedBy'])
            ->whereNotNull('start_at')
            ->whereBetween('start_at', [$from, $until])
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
                    'id' => 'wo-'.$wo->id,
                    'title' => $woNumber.' — '.$tag,
                    'start' => $wo->start_at->toDateTimeString(),
                    'end' => $wo->finish_at?->toDateTimeString(),
                    'color' => $color,
                    'extendedProps' => [
                        'type' => 'work_order',
                        'status' => $wo->status,
                        'equipment' => $wo->equipment->equipmentType?->name.' ('.$tag.')',
                        'area' => $wo->equipment->area->name ?? '-',
                        'classification' => $wo->classification,
                        'issuedBy' => $wo->issuedBy?->name ?? '-',
                        'note' => $wo->note,
                        'recordId' => $wo->id,
                    ],
                ];
            });

        MaintenancePlan::with(['equipment.equipmentType', 'equipment.area', 'createdBy'])
            ->where('status', 'Active')
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $from->toDateString()))
            ->whereDate('start_date', '<=', $until->toDateString())
            ->get()
            ->each(function (MaintenancePlan $mp) use (&$events) {
                $tag = $mp->equipment->tag_number;

                $events[] = [
                    'id' => 'plan-'.$mp->id,
                    'title' => 'Plan: '.$tag,
                    'start' => $mp->start_date->toDateString(),
                    'end' => $mp->end_date?->copy()->addDay()->toDateString(),
                    'color' => '#7c3aed',
                    'display' => 'background',
                    'extendedProps' => [
                        'type' => 'plan',
                        'status' => $mp->status,
                        'equipment' => $mp->equipment->equipmentType?->name.' ('.$tag.')',
                        'area' => $mp->equipment->area->name ?? '-',
                        'classification' => $mp->maintenance_classification,
                        'interval' => $mp->interval,
                        'description' => $mp->description,
                        'createdBy' => $mp->createdBy?->name ?? '-',
                        'recordId' => $mp->id,
                    ],
                ];
            });

        MaintenanceSchedule::with(['equipment.equipmentType', 'equipment.area', 'maintenancePlan.equipment'])
            ->whereBetween('scheduled_date', [$from->toDateString(), $until->toDateString()])
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
                    'id' => 'schedule-'.$ms->id,
                    'title' => $tag.' — '.$ms->status,
                    'start' => $ms->scheduled_date->toDateString(),
                    'color' => $color,
                    'extendedProps' => [
                        'type' => 'schedule',
                        'status' => $ms->status,
                        'equipment' => $ms->equipment->equipmentType?->name.' ('.$tag.')',
                        'area' => $ms->equipment->area->name ?? '-',
                        'description' => $ms->description,
                        'planName' => $ms->maintenancePlan
                            ? ($ms->maintenancePlan->equipment->tag_number ?? '-').' — '.$ms->maintenancePlan->interval
                            : '-',
                        'rescheduledFrom' => $ms->rescheduled_from?->format('d M Y'),
                        'recordId' => $ms->id,
                    ],
                ];
            });

        return $events;
    }
}
