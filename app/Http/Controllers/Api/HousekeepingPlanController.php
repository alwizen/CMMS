<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HousekeepingPlanFacilityResource;
use App\Http\Resources\HousekeepingPlanResource;
use App\Http\Resources\HousekeepingPlanTaskResource;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanFacility;
use App\Models\HousekeepingPlanTask;
use App\Models\HousekeepingTask;
use App\Support\HousekeepingPlanGenerator;
use App\Support\HousekeepingRole;
use App\Support\HousekeepingVisibility;
use Carbon\Carbon;
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
                'users:id,name',
                'tasks:id,housekeeping_plan_id,status,completed_at',
            ])
            ->orderByDesc('plan_date')
            ->orderByDesc('id');

        // Patra-jasa hanya melihat plan area yang ia pertanggungjawabkan.
        HousekeepingVisibility::scopePlans($query, $request->user());

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

    public function show(Request $request, int $id): JsonResponse
    {
        abort_unless(
            HousekeepingVisibility::canViewPlan($request->user(), $id),
            403,
            'Anda tidak berhak melihat plan ini.',
        );

        $plan = HousekeepingPlan::query()
            ->with([
                'area.company:id,name',
                'area.supervisor:id,name',
                'createdBy:id,name',
                'approvedBy:id,name',
                'manpowers',
                'users:id,name,email,employee_number',
                'facilities.facility',
                'tasks.facility',
                'tasks.masterTask',
                'tasks.planFacility',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $plan->id,
                'plan_number' => $plan->plan_number,
                'plan_date' => $plan->plan_date?->format('Y-m-d'),
                'status' => $plan->status,
                'approval_status' => $plan->approval_status,
                'approval_notes' => $plan->approval_notes,
                'approved_at' => $plan->approved_at?->toIso8601String(),
                'approved_by' => $plan->approvedBy?->name,
                'note' => $plan->note,
                'progress_percent' => $plan->progress_percent,
                'sla_days' => $plan->area?->sla_days,
                'due_date' => $plan->due_date?->format('Y-m-d'),
                'sla_status' => $plan->sla_status,
                'is_overdue' => $plan->is_overdue,
                'created_at' => $plan->created_at?->toIso8601String(),
                'created_by' => $plan->createdBy?->name,
                'area' => [
                    'id' => $plan->area?->id,
                    'name' => $plan->area?->name,
                    'code' => $plan->area?->code,
                    'company' => $plan->area?->company?->name,
                    'supervisor' => $plan->area?->supervisor?->name,
                    'area_size' => $plan->area?->area_size,
                    'sla_days' => $plan->area?->sla_days,
                    'polygon' => $plan->area?->polygon,
                ],
                'manpowers' => $plan->manpowers->map(fn ($manpower) => [
                    'id' => $manpower->id,
                    'name' => $manpower->name,
                    'employee_number' => $manpower->employee_number,
                    'phone' => $manpower->phone,
                    'note' => $manpower->pivot?->note,
                ])->values(),
                'users' => $plan->users->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_number' => $user->employee_number,
                ])->values(),
                'facilities' => $plan->facilities->map(fn ($planFacility) => [
                    'id' => $planFacility->id,
                    'facility' => $planFacility->facility ? [
                        'id' => $planFacility->facility->id,
                        'name' => $planFacility->facility->name,
                        'code' => $planFacility->facility->code,
                    ] : null,
                    'photo_before' => $planFacility->photo_before ?? [],
                    'photo_after' => $planFacility->photo_after ?? [],
                ])->values(),
                'tasks' => HousekeepingPlanTaskResource::collection($plan->tasks),
            ],
        ]);
    }

    /**
     * Perbarui status/catatan satu item checklist.
     * Foto dokumentasi dikelola per fasilitas, bukan per item checklist.
     */
    public function updateTask(Request $request, int $planId, int $taskId): JsonResponse
    {
        $this->authorizeExecutor($request);

        $plan = HousekeepingPlan::findOrFail($planId);

        $task = HousekeepingPlanTask::where('housekeeping_plan_id', $plan->id)->findOrFail($taskId);

        $request->validate([
            'status' => 'sometimes|string|in:Pending,In Progress,Done',
            'note' => 'sometimes|nullable|string|max:5000',
        ]);

        $newStatus = $request->input('status', $task->status);

        $planFacility = $task->planFacility;

        if ($planFacility !== null) {
            if ($newStatus === 'In Progress' && empty($planFacility->photo_before)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload foto before fasilitas terlebih dahulu sebelum memulai pekerjaan.',
                ], 422);
            }

            if ($newStatus === 'Done' && empty($planFacility->photo_after)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload foto after fasilitas terlebih dahulu sebelum menyelesaikan pekerjaan.',
                ], 422);
            }
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
            'data' => new HousekeepingPlanTaskResource($task->fresh(['facility', 'masterTask', 'planFacility'])),
        ]);
    }

    /**
     * Unggah foto before/after untuk satu fasilitas pada plan.
     * Parameter "type" menentukan jenis foto: "before" atau "after".
     */
    public function uploadFacilityPhotos(Request $request, int $planId, int $planFacilityId): JsonResponse
    {
        $this->authorizeExecutor($request);

        $plan = HousekeepingPlan::findOrFail($planId);

        $planFacility = HousekeepingPlanFacility::where('housekeeping_plan_id', $plan->id)
            ->findOrFail($planFacilityId);

        $request->validate([
            'type' => 'required|string|in:before,after',
            'photos' => 'required|array|min:1|max:5',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $type = $request->input('type');
        $column = $type === 'before' ? 'photo_before' : 'photo_after';
        $directory = $type === 'before'
            ? 'housekeeping/plan-facilities/before'
            : 'housekeeping/plan-facilities/after';

        $existing = $planFacility->{$column} ?? [];
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

        $planFacility->update([$column => array_values($paths)]);

        return response()->json([
            'success' => true,
            'message' => 'Foto '.$type.' berhasil diunggah.',
            'data' => new HousekeepingPlanFacilityResource($planFacility->fresh('facility')),
        ]);
    }

    public function deleteFacilityPhoto(Request $request, int $planId, int $planFacilityId): JsonResponse
    {
        $this->authorizeExecutor($request);

        $plan = HousekeepingPlan::findOrFail($planId);

        $planFacility = HousekeepingPlanFacility::where('housekeeping_plan_id', $plan->id)
            ->findOrFail($planFacilityId);

        $request->validate([
            'type' => 'required|string|in:before,after',
            'path' => 'required|string',
        ]);

        $type = $request->input('type');
        $column = $type === 'before' ? 'photo_before' : 'photo_after';
        $path = $request->input('path');

        $photos = $planFacility->{$column} ?? [];

        if (! in_array($path, $photos, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Foto tidak ditemukan pada fasilitas ini.',
            ], 404);
        }

        Storage::disk('public')->delete($path);

        $planFacility->update([
            $column => array_values(array_diff($photos, [$path])),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto berhasil dihapus.',
            'data' => new HousekeepingPlanFacilityResource($planFacility->fresh('facility')),
        ]);
    }

    /**
     * Buat plan harian housekeeping baru.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizePlanManager($request);

        $request->validate([
            'housekeeping_area_id' => 'required|integer|exists:housekeeping_areas,id',
            'plan_date' => 'required|date',
            'note' => 'nullable|string|max:5000',
        ]);

        $area = HousekeepingArea::findOrFail($request->input('housekeeping_area_id'));
        $planDate = Carbon::parse($request->input('plan_date'))->startOfDay();

        $blocking = app(HousekeepingPlanGenerator::class)->blockingPlan($area, $planDate);

        if ($blocking !== null) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'Area %s masih punya Plan %s yang belum selesai (batas SLA %s). Selesaikan plan tersebut atau tunggu SLA lewat.',
                    $area->name,
                    $blocking->plan_number,
                    $blocking->due_date?->format('d M Y') ?? '-',
                ),
            ], 422);
        }

        $plan = HousekeepingPlan::create([
            'housekeeping_area_id' => $area->id,
            'plan_date' => $planDate->toDateString(),
            'note' => $request->input('note'),
            'status' => 'Draft',
            'created_by' => auth()->id(),
        ]);

        $plan->load(['area.company:id,name', 'area.supervisor:id,name', 'manpowers', 'users', 'tasks', 'facilities.facility']);

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
        $this->authorizePlanManager($request);

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
        $this->authorizePlanManager($request);

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
            'data' => new HousekeepingPlanTaskResource($task->fresh(['facility', 'masterTask', 'planFacility'])),
        ], 201);
    }

    /**
     * Hapus item checklist dari plan.
     */
    public function deleteTask(Request $request, int $planId, int $taskId): JsonResponse
    {
        $this->authorizePlanManager($request);

        $plan = HousekeepingPlan::findOrFail($planId);

        $task = HousekeepingPlanTask::where('housekeeping_plan_id', $plan->id)->findOrFail($taskId);
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item checklist berhasil dihapus.',
        ]);
    }

    /**
     * Generate Plan Harian otomatis dari master checklist fasilitas di area.
     * Area tanpa checklist aktif / plan tanggal sama akan dilewati.
     */
    public function generate(Request $request): JsonResponse
    {
        $this->authorizePlanManager($request);

        $request->validate([
            'area_ids' => 'required|array|min:1',
            'area_ids.*' => 'integer|exists:housekeeping_areas,id',
            'plan_date' => 'required|date',
            'manpower_ids' => 'nullable|array',
            'manpower_ids.*' => 'integer|exists:housekeeping_manpowers,id',
            'note' => 'nullable|string|max:5000',
        ]);

        $result = app(HousekeepingPlanGenerator::class)->generate(
            areaIds: $request->input('area_ids'),
            planDate: $request->input('plan_date'),
            manpowerIds: $request->input('manpower_ids', []),
            note: $request->input('note'),
            createdBy: auth()->id(),
        );

        if ($result['created'] === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada Plan Harian yang dibuat.',
                'created' => 0,
                'skipped' => $result['skip_reasons'],
            ], 422);
        }

        $plans = collect($result['plans'])
            ->map(function (HousekeepingPlan $plan): HousekeepingPlanResource {
                $plan->load(['area.company:id,name', 'area.supervisor:id,name', 'manpowers', 'users', 'tasks', 'facilities.facility']);

                return new HousekeepingPlanResource($plan);
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => $result['created'].' Plan Harian berhasil dibuat.',
            'created' => $result['created'],
            'data' => $plans,
            'skipped' => $result['skip_reasons'],
        ], 201);
    }

    /**
     * Ubah tanggal/catatan plan.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizePlanManager($request);

        $plan = HousekeepingPlan::findOrFail($id);

        $request->validate([
            'plan_date' => 'sometimes|date',
            'note' => 'nullable|string|max:5000',
        ]);

        $plan->update($request->only(['plan_date', 'note']));

        $plan->load(['area.company:id,name', 'area.supervisor:id,name', 'manpowers', 'users', 'tasks', 'facilities.facility']);

        return response()->json([
            'success' => true,
            'message' => 'Plan housekeeping berhasil diperbarui.',
            'data' => new HousekeepingPlanResource($plan),
        ]);
    }

    /**
     * Hapus plan beserta checklist-nya.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizePlanManager($request);

        $plan = HousekeepingPlan::findOrFail($id);
        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan housekeeping berhasil dihapus.',
        ]);
    }

    /**
     * Lepas satu manpower dari plan.
     */
    public function removeManpower(Request $request, int $planId, int $manpowerId): JsonResponse
    {
        $this->authorizePlanManager($request);

        $plan = HousekeepingPlan::findOrFail($planId);

        $plan->manpowers()->detach($manpowerId);
        $plan->load('manpowers');

        return response()->json([
            'success' => true,
            'message' => 'Manpower berhasil dilepas dari plan.',
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
     * Pastikan user berhak mengelola struktur Plan Harian (SPV GOS).
     */
    protected function authorizePlanManager(Request $request): void
    {
        abort_unless(
            HousekeepingRole::canManagePlan($request->user()),
            403,
            'Hanya SPV GOS yang boleh membuat atau mengubah Plan Harian.',
        );
    }

    /**
     * Pastikan user berhak mengeksekusi checklist (patra-jasa).
     */
    protected function authorizeExecutor(Request $request): void
    {
        abort_unless(
            HousekeepingRole::canExecute($request->user()),
            403,
            'Hanya petugas housekeeping (patra-jasa) yang boleh mengerjakan checklist.',
        );
    }
}
