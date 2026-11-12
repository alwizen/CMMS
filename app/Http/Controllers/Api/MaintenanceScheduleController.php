<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaintenanceScheduleResource;
use App\Models\MaintenanceSchedule;
use App\Support\UserVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MaintenanceSchedule::with(['equipment.equipmentType', 'equipment.area', 'maintenancePlan'])
            ->orderByDesc('scheduled_date');

        UserVisibility::scopeSchedules($query, $request->user());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('scheduled_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('scheduled_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('equipment', function ($q) use ($search) {
                $q->where('tag_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $schedules = $query->paginate($this->perPage($request));

        return response()->json([
            'success' => true,
            'data' => MaintenanceScheduleResource::collection($schedules),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'per_page' => $schedules->perPage(),
                'total' => $schedules->total(),
            ],
        ]);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $schedule = MaintenanceSchedule::with([
            'equipment.equipmentType',
            'equipment.area',
            'maintenancePlan',
            'workOrders',
        ])->findOrFail($id);

        abort_unless(
            UserVisibility::canAccessSchedule($schedule, $request->user()),
            403,
            'Anda tidak memiliki akses untuk maintenance schedule ini.',
        );

        return response()->json([
            'success' => true,
            'data' => new MaintenanceScheduleResource($schedule),
        ]);
    }
}
