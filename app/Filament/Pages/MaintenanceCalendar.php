<?php

namespace App\Filament\Pages;

use App\Models\WorkOrder;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MaintenanceCalendar extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Kalender Maintenance';

    protected static string|UnitEnum|null $navigationGroup = 'Maintenance';

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
