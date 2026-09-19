<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaintenanceScheduleResource;
use App\Models\MaintenanceSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MaintenanceSchedule::with(['equipment.area', 'maintenancePlan'])
            ->orderByDesc('scheduled_date');

        if ($request->user()->hasRole('technician')) {
            $query->whereHas('maintenancePlan', function ($q) use ($request) {
                $q->where('technician_coordinator_id', $request->user()->id);
            });
        }

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
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('tag_number', 'like', "%{$search}%");
            });
        }

        $schedules = $query->paginate($request->get('per_page', 15));

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

    public function show($id): JsonResponse
    {
        $schedule = MaintenanceSchedule::with([
            'equipment.area',
            'maintenancePlan',
            'workOrders',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new MaintenanceScheduleResource($schedule),
        ]);
    }
}
