<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EquipmentResource;
use App\Http\Resources\MaintenanceHistoryResource;
use App\Models\Area;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Equipment::with(['area.company', 'equipmentType.responsibleUsers'])
            ->orderBy('tag_number');

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->filled('equipment_type_id')) {
            $query->where('equipment_type_id', $request->equipment_type_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tag_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('equipmentType', fn ($type) => $type->where('name', 'like', "%{$search}%"));
            });
        }

        $equipment = $query->paginate($this->perPage($request));

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

    /**
     * Opsi filter untuk layar Equipment (daftar area & tipe equipment).
     */
    public function filterOptions(): JsonResponse
    {
        $areas = Area::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $types = EquipmentType::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'areas' => $areas,
                'equipment_types' => $types,
            ],
        ]);
    }

    public function show($id): JsonResponse
    {
        $equipment = Equipment::with([
            'area.company',
            'equipmentType.responsibleUsers',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new EquipmentResource($equipment),
        ]);
    }

    /**
     * Riwayat maintenance untuk satu equipment (terbaru dulu).
     */
    public function maintenanceHistories(Request $request, $id): JsonResponse
    {
        Equipment::query()->findOrFail($id);

        $histories = MaintenanceHistory::query()
            ->where('equipment_id', $id)
            ->with(['performedBy', 'workOrder.workOrderActivities.activity'])
            ->orderByDesc('maintenance_date')
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return response()->json([
            'success' => true,
            'data' => MaintenanceHistoryResource::collection($histories),
            'meta' => [
                'current_page' => $histories->currentPage(),
                'last_page' => $histories->lastPage(),
                'per_page' => $histories->perPage(),
                'total' => $histories->total(),
            ],
        ]);
    }
}
