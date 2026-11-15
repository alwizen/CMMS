<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;
use App\Support\UserVisibility;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceCalendarController extends Controller
{
    /**
     * Event kalender (work order) yang sudah difilter sesuai akun.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $from = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfMonth()->subMonth();
        $until = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->startOfMonth()->addMonths(2)->subDay()->endOfDay();

        $events = $this->workOrderEvents($request, $from, $until);

        usort($events, fn (array $a, array $b): int => strcmp($a['date'], $b['date']));

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $from->toDateString(),
                'until' => $until->toDateString(),
                'events' => $events,
            ],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function workOrderEvents(Request $request, Carbon $from, Carbon $until): array
    {
        $query = WorkOrder::query()
            ->with(['equipment.equipmentType'])
            ->whereNotNull('start_at')
            ->whereBetween('start_at', [$from, $until])
            ->orderBy('start_at');

        UserVisibility::scopeWorkOrders($query, $request->user());

        return $query->get()->map(fn (WorkOrder $workOrder): array => [
            'id' => 'wo-'.$workOrder->id,
            'record_id' => $workOrder->id,
            'type' => 'work_order',
            'date' => $workOrder->start_at?->toDateString(),
            'time' => $workOrder->start_at?->format('H:i'),
            'title' => $workOrder->work_order_number,
            'status' => $workOrder->status,
            'classification' => $workOrder->classification,
            'equipment_tag' => $workOrder->equipment?->tag_number,
            'equipment_type' => $workOrder->equipment?->equipmentType?->name,
            'work_order_number' => $workOrder->work_order_number,
            'description' => $workOrder->note,
        ])->values()->all();
    }
}
