<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HousekeepingPlanResource;
use App\Http\Resources\HousekeepingPlanTaskResource;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\HousekeepingTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HousekeepingPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = HousekeepingPlan::query()
            ->with([
                'area.company:id,name',
                'area.supervisor:id,name',
                'manpowers:id',
                'tasks:id,housekeeping_plan_id,status',
            ])
            ->orderByDesc('plan_date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('area_id')) {
            $query->where('housekeeping_area_id', $request->area_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('plan_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('plan_date', '<=', $request->date_to);
        }

        $plans = $query->paginate($this->perPage($request));

        return response()->json([
            'success' => true,
            'data' => HousekeepingPlanResource::collection($plans),
            'meta' => [
                'current_page' => $plans->currentPage(),
                'last_page' => $plans->lastPage(),
                'per_page' => $plans->perPage(),
                'total' => $plans->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $plan = HousekeepingPlan::query()
            ->with([
                'area.company:id,name',
                'area.supervisor:id,name',
                'createdBy:id,name',
                'manpowers',
                'tasks.facility',
                'tasks.masterTask',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $plan->id,
                'plan_number' => $plan->plan_number,
                'plan_date' => $plan->plan_date?->format('Y-m-d'),
                'status' => $plan->status,
                'note' => $plan->note,
                'created_at' => $plan->created_at?->toIso8601String(),
                'created_by' => $plan->createdBy?->name,
                'area' => [
                    'id' => $plan->area?->id,
                    'name' => $plan->area?->name,
                    'code' => $plan->area?->code,
                    'company' => $plan->area?->company?->name,
                    'supervisor' => $plan->area?->supervisor?->name,
                    'area_size' => $plan->area?->area_size,
                    'polygon' => $plan->area?->polygon,
                ],
                'manpowers' => $plan->manpowers->map(fn ($manpower) => [
                    'id' => $manpower->id,
                    'name' => $manpower->name,
                    'employee_number' => $manpower->employee_number,
                    'phone' => $manpower->phone,
                    'note' => $manpower->pivot?->note,
                ])->values(),
                'tasks' => HousekeepingPlanTaskResource::collection($plan->tasks),
            ],
        ]);
    }

    /**
     * Perbarui status/catatan satu item checklist.
     * Status "In Progress" wajib sudah punya foto before, "Done" wajib sudah punya foto after.
     */
    public function updateTask(Request $request, int $planId, int $taskId): JsonResponse
    {
        $plan = HousekeepingPlan::findOrFail($planId);

        $task = HousekeepingPlanTask::where('housekeeping_plan_id', $plan->id)->findOrFail($taskId);

        $request->validate([
            'status' => 'sometimes|string|in:Pending,In Progress,Done',
            'note' => 'sometimes|nullable|string|max:5000',
        ]);

        $newStatus = $request->input('status', $task->status);

        if ($newStatus === 'In Progress' && empty($task->photo_before)) {
            return response()->json([
                'success' => false,
                'message' => 'Upload foto before terlebih dahulu sebelum memulai pekerjaan.',
            ], 422);
        }

        if ($newStatus === 'Done' && empty($task->photo_after)) {
            return response()->json([
                'success' => false,
                'message' => 'Upload foto after terlebih dahulu sebelum menyelesaikan pekerjaan.',
            ], 422);
        }

        $data = [];

        if ($request->has('status')) {
            $data['status'] = $newStatus;
        }

        if ($request->has('note')) {
            $data['note'] = $request->input('note');
        }

        $task->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Item checklist berhasil diperbarui.',
            'data' => new HousekeepingPlanTaskResource($task->fresh(['facility', 'masterTask'])),
        ]);
    }

    /**
     * Unggah foto before/after untuk satu item checklist.
     * Parameter "type" menentukan jenis foto: "before" atau "after".
     */
    public function uploadTaskPhotos(Request $request, int $planId, int $taskId): JsonResponse
    {
        $plan = HousekeepingPlan::findOrFail($planId);

        $task = HousekeepingPlanTask::where('housekeeping_plan_id', $plan->id)->findOrFail($taskId);

        $request->validate([
            'type' => 'required|string|in:before,after',
            'photos' => 'required|array|min:1|max:5',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $type = $request->input('type');
        $column = $type === 'before' ? 'photo_before' : 'photo_after';
        $directory = $type === 'before'
            ? 'housekeeping/plan-tasks/before'
            : 'housekeeping/plan-tasks/after';

        $existing = $task->{$column} ?? [];
        $remaining = max(0, 5 - count($existing));

        if ($remaining === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal 5 foto per jenis. Hapus salah satu foto terlebih dahulu.',
            ], 422);
        }

        $files = array_slice($request->file('photos', []), 0, $remaining);

        $paths = $existing;

        foreach ($files as $file) {
            $paths[] = $file->store($directory, 'public');
        }

        $task->update([$column => array_values($paths)]);

        return response()->json([
            'success' => true,
            'message' => 'Foto '.$type.' berhasil diunggah.',
            'data' => new HousekeepingPlanTaskResource($task->fresh(['facility', 'masterTask'])),
        ]);
    }

    public function deleteTaskPhoto(Request $request, int $planId, int $taskId): JsonResponse
    {
        $plan = HousekeepingPlan::findOrFail($planId);

        $task = HousekeepingPlanTask::where('housekeeping_plan_id', $plan->id)->findOrFail($taskId);

        $request->validate([
            'type' => 'required|string|in:before,after',
            'path' => 'required|string',
        ]);

        $type = $request->input('type');
        $column = $type === 'before' ? 'photo_before' : 'photo_after';
        $path = $request->input('path');

        $photos = $task->{$column} ?? [];

        if (! in_array($path, $photos, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tidak ditemukan pada item checklist ini.',
            ], 404);
        }

        Storage::disk('public')->delete($path);

        $task->update([
            $column => array_values(array_diff($photos, [$path])),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto berhasil dihapus.',
            'data' => new HousekeepingPlanTaskResource($task->fresh(['facility', 'masterTask'])),
        ]);
    }

    /**
     * Buat plan harian housekeeping baru.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'housekeeping_area_id' => 'required|integer|exists:housekeeping_areas,id',
            'plan_date' => 'required|date',
            'note' => 'nullable|string|max:5000',
        ]);

        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $request->input('housekeeping_area_id'),
            'plan_date' => $request->input('plan_date'),
            'note' => $request->input('note'),
            'status' => 'Draft',
            'created_by' => auth()->id(),
        ]);

        $plan->load(['area.company:id,name', 'area.supervisor:id,name', 'manpowers', 'tasks']);

        return response()->json([
            'success' => true,
            'message' => 'Plan housekeeping berhasil dibuat.',
            'data' => new HousekeepingPlanResource($plan),
        ], 201);
    }

    /**
     * Tugaskan satu atau beberapa manpower ke plan.
     */
    public function addManpowers(Request $request, int $planId): JsonResponse
    {
        $plan = HousekeepingPlan::findOrFail($planId);

        $request->validate([
            'manpower_ids' => 'required|array|min:1',
            'manpower_ids.*' => 'integer|exists:housekeeping_manpowers,id',
        ]);

        $manpowerIds = $request->input('manpower_ids');

        // Pastikan manpower berasal dari area yang sama dengan plan.
        $validIds = $plan->area->manpowers()
            ->whereIn('id', $manpowerIds)
            ->pluck('id')
            ->all();

        if (count($validIds) !== count(array_unique($manpowerIds))) {
            return response()->json([
                'success' => false,
                'message' => 'Sebagian manpower tidak berasal dari area plan ini.',
            ], 422);
        }

        $plan->manpowers()->syncWithoutDetaching($validIds);
        $plan->load('manpowers');

        return response()->json([
            'success' => true,
            'message' => 'Manpower berhasil ditugaskan ke plan.',
            'data' => $plan->manpowers->map(fn ($manpower) => [
                'id' => $manpower->id,
                'name' => $manpower->name,
                'employee_number' => $manpower->employee_number,
                'phone' => $manpower->phone,
                'note' => $manpower->pivot?->note,
            ])->values(),
        ]);
    }

    /**
     * Tambahkan item checklist ke plan.
     * Bisa dari master checklist (housekeeping_task_id) atau nama bebas.
     */
    public function addTask(Request $request, int $planId): JsonResponse
    {
        $plan = HousekeepingPlan::findOrFail($planId);

        $request->validate([
            'housekeeping_task_id' => 'nullable|integer|exists:housekeeping_tasks,id',
            'housekeeping_facility_id' => 'nullable|integer|exists:housekeeping_facilities,id',
            'name' => 'required_without:housekeeping_task_id|nullable|string|max:150',
        ]);

        $masterTask = null;

        if ($request->filled('housekeeping_task_id')) {
            $masterTask = HousekeepingTask::with('facility')->findOrFail($request->input('housekeeping_task_id'));
        }

        $facilityId = $request->input('housekeeping_facility_id')
            ?? $masterTask?->housekeeping_facility_id;

        $name = $request->input('name') ?? $masterTask?->name;

        if (blank($name)) {
            return response()->json([
                'success' => false,
                'message' => 'Nama item checklist wajib diisi.',
            ], 422);
        }

        $task = HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $plan->id,
            'housekeeping_task_id' => $masterTask?->id,
            'housekeeping_facility_id' => $facilityId,
            'name' => $name,
            'status' => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item checklist berhasil ditambahkan.',
            'data' => new HousekeepingPlanTaskResource($task->fresh(['facility', 'masterTask'])),
        ], 201);
    }

    /**
     * Hapus item checklist dari plan.
     */
    public function deleteTask(int $planId, int $taskId): JsonResponse
    {
        $plan = HousekeepingPlan::findOrFail($planId);

        $task = HousekeepingPlanTask::where('housekeeping_plan_id', $plan->id)->findOrFail($taskId);
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item checklist berhasil dihapus.',
        ]);
    }
}
