<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HousekeepingArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HousekeepingAreaController extends Controller
{
    /**
     * Daftar area housekeeping untuk pembuatan plan di aplikasi.
     */
    public function index(Request $request): JsonResponse
    {
        $query = HousekeepingArea::query()
            ->where('is_active', true)
            ->with('company:id,name')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $areas = $query->get()->map(fn (HousekeepingArea $area) => [
            'id' => $area->id,
            'name' => $area->name,
            'code' => $area->code,
            'company' => $area->company?->name,
            'area_size' => $area->area_size,
            'supervisor' => $area->supervisor?->name,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => $areas,
        ]);
    }

    /**
     * Manpower aktif pada suatu area (untuk ditugaskan ke plan).
     */
    public function manpowers(int $id): JsonResponse
    {
        $area = HousekeepingArea::findOrFail($id);

        $manpowers = $area->manpowers()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($manpower) => [
                'id' => $manpower->id,
                'name' => $manpower->name,
                'employee_number' => $manpower->employee_number,
                'phone' => $manpower->phone,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $manpowers,
        ]);
    }

    /**
     * Fasilitas pada area beserta master checklist-nya
     * (untuk menambah item checklist ke plan).
     */
    public function facilities(int $id): JsonResponse
    {
        $area = HousekeepingArea::findOrFail($id);

        $facilities = $area->facilities()
            ->where('is_active', true)
            ->with(['tasks' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(fn ($facility) => [
                'id' => $facility->id,
                'name' => $facility->name,
                'code' => $facility->code,
                'tasks' => $facility->tasks->map(fn ($task) => [
                    'id' => $task->id,
                    'name' => $task->name,
                ])->values(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $facilities,
        ]);
    }
}
