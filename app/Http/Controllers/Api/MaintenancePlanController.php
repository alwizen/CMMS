<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaintenancePlanResource;
use App\Models\MaintenancePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenancePlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MaintenancePlan::with(['equipment.area', 'createdBy', 'technicianCoordinator'])
            ->orderByDesc('created_at');

        if ($request->user()->hasRole('technician')) {
            $query->where('technician_coordinator_id', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('equipment', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('tag_number', 'like', "%{$search}%");
            });
        }

        $plans = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => MaintenancePlanResource::collection($plans),
            'meta' => [
                'current_page' => $plans->currentPage(),
                'last_page' => $plans->lastPage(),
                'per_page' => $plans->perPage(),
                'total' => $plans->total(),
            ],
        ]);
    }

    public function show($id): JsonResponse
    {
        $plan = MaintenancePlan::with([
            'equipment.area',
            'createdBy',
            'technicianCoordinator',
            'planActivities.activity',
            'maintenanceSchedules',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new MaintenancePlanResource($plan),
        ]);
    }
}
