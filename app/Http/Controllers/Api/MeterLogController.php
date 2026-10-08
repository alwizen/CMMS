<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeterLogResource;
use App\Models\Equipment;
use App\Models\MeterLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeterLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MeterLog::with(['equipment', 'recordedBy'])
            ->orderByDesc('reading_date')
            ->orderByDesc('id');

        if ($request->filled('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('reading_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('reading_date', '<=', $request->date_to);
        }

        $logs = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => MeterLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function byEquipment(Request $request, int $equipmentId): JsonResponse
    {
        $equipment = Equipment::findOrFail($equipmentId);

        $logs = MeterLog::with(['recordedBy'])
            ->where('equipment_id', $equipment->id)
            ->orderByDesc('reading_date')
            ->orderByDesc('id')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => MeterLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'reading_date' => 'required|date',
            'value' => 'required|numeric|min:0',
            'recorded_by' => 'nullable|exists:users,id',
        ]);

        $validated['recorded_by'] = $validated['recorded_by'] ?? $request->user()->id;

        $meterLog = MeterLog::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Meter log berhasil dicatat.',
            'data' => new MeterLogResource($meterLog->load(['equipment', 'recordedBy'])),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $meterLog = MeterLog::with(['equipment', 'recordedBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new MeterLogResource($meterLog),
        ]);
    }
}
