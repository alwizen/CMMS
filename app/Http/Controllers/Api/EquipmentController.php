<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Equipment::with(['area.company', 'equipmentType'])->orderBy('tag_number');

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('tag_number', 'like', "%{$search}%")
                    ->orWhere('equipment_type_id', 'like', "%{$search}%");
            });
        }

        $equipment = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => EquipmentResource::collection($equipment),
            'meta' => [
                'current_page' => $equipment->currentPage(),
                'last_page' => $equipment->lastPage(),
                'per_page' => $equipment->perPage(),
                'total' => $equipment->total(),
            ],
        ]);
    }

    public function show($id): JsonResponse
    {
        $equipment = Equipment::with([
            'area.company',
            'maintenancePlans',
            'maintenanceSchedules',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new EquipmentResource($equipment),
        ]);
    }
}
