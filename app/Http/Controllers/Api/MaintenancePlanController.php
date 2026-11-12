<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaintenancePlanResource;
use App\Models\MaintenancePlan;
use App\Support\UserVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenancePlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MaintenancePlan::with(['equipment.equipmentType', 'equipment.area', 'createdBy', 'technicianCoordinator'])
            ->orderByDesc('created_at');

        UserVisibility::scopePlans($query, $request->user());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('equipment', function ($q) use ($search) {
                $q->where('tag_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $plans = $query->paginate($this->perPage($request));

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

    public function show(Request $request, $id): JsonResponse
    {
        $plan = MaintenancePlan::with([
            'equipment.equipmentType',
            'equipment.area',
            'createdBy',
            'technicianCoordinator',
            'planActivities.activity',
            'maintenanceSchedules',
        ])->findOrFail($id);

        abort_unless(
            UserVisibility::canAccessPlan($plan, $request->user()),
            403,
            'Anda tidak memiliki akses untuk maintenance plan ini.',
        );

        return response()->json([
            'success' => true,
            'data' => new MaintenancePlanResource($plan),
        ]);
    }
}
