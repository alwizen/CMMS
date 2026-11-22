<?php

namespace App\Filament\App\Pages;

use App\Models\WorkOrder;
use App\Support\UserVisibility;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MaintenanceCalendar extends \App\Filament\Pages\MaintenanceCalendar
{
    protected static ?string $navigationLabel = 'Kalender';

    protected static string|UnitEnum|null $navigationGroup = 'Pekerjaan';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('View:MaintenanceCalendar');
    }

    public function getCalendarEvents(): array
    {
        $events = [];

        [$from, $until] = $this->calendarWindow();

        $query = WorkOrder::with(['equipment.equipmentType', 'equipment.area', 'issuedBy'])
            ->whereNotNull('start_at')
            ->whereBetween('start_at', [$from, $until]);

        UserVisibility::scopeWorkOrders($query, auth()->user());

        $query->get()->each(function (WorkOrder $wo) use (&$events) {
            $color = match ($wo->status) {
                'Completed' => '#16a34a',
                'In Progress' => '#2563eb',
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
                    'time' => $wo->start_at->format('H:i'),
                    'tag' => $tag,
                    'equipment' => $wo->equipment->equipmentType?->name.' ('.$tag.')',
                    'area' => $wo->equipment->area->name ?? '-',
                    'classification' => $wo->classification,
                    'issuedBy' => $wo->issuedBy?->name ?? '-',
                    'note' => $wo->note,
                    'recordId' => $wo->id,
                ],
            ];
        });

        return $events;
    }
}
