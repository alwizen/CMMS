<?php

namespace Tests\Feature;

use App\Filament\Resources\MaintenanceRequests\Pages\ViewMaintenanceRequests;
use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceRequest;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RequestApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $spv;

    protected User $penanggungJawab;

    protected Equipment $equipment;

    protected Activity $activityA;

    protected Activity $activityB;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();
        config(['app.env' => 'local']);
        Gate::before(fn (): bool => true);

        $this->spv = User::factory()->create();
        $this->penanggungJawab = User::factory()->create(['fungsi' => 'MOS LTSA']);
        $this->penanggungJawab->assignRole(Role::findOrCreate('mos', 'web'));

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'pompa', 'name' => 'Pompa']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
        ]);

        $this->activityA = Activity::create([
            'equipment_type_id' => $type->id,
            'name' => 'Inspeksi Kebocoran Seal',
            'type' => 'visual_check',
            'maintenance_classification' => 'corrective',
            'interval' => 'monthly',
            'answer_type' => 'qualitative',
            'status' => true,
        ]);

        $this->activityB = Activity::create([
            'equipment_type_id' => $type->id,
            'name' => 'Uji Tekanan',
            'type' => 'testing',
            'maintenance_classification' => 'corrective',
            'interval' => 'monthly',
            'answer_type' => 'quantitative',
            'unit' => 'bar',
            'status' => true,
        ]);
    }

    protected function makeOpenRequest(): MaintenanceRequest
    {
        return MaintenanceRequest::create([
            'equipment_id' => $this->equipment->id,
            'reported_by' => $this->spv->id,
            'operation_status' => 'Faulty',
            'description' => 'Seal pompa bocor',
            'damage_date' => now()->toDateString(),
            'status' => MaintenanceRequest::STATUSES['Open'],
        ]);
    }

    public function test_approve_turns_request_into_pending_work_order_owned_by_fungsi(): void
    {
        $request = $this->makeOpenRequest();

        $this->actingAs($this->spv);

        Livewire::test(ViewMaintenanceRequests::class, ['record' => $request->getRouteKey()])
            ->mountAction('approve')
            ->fillForm([
                'start_date' => now()->addDay()->toDateString(),
                'fungsi' => 'MOS LTSA',
                'classification' => 'Corrective',
                'approval_notes' => 'Segera ditangani',
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame(MaintenanceRequest::STATUSES['Approved'], $request->fresh()->status);
        $this->assertSame($this->spv->id, $request->fresh()->approved_by);

        $workOrder = WorkOrder::where('maintenance_request_id', $request->id)->first();

        $this->assertNotNull($workOrder);
        $this->assertSame(WorkOrder::STATUSES['Pending'], $workOrder->status);
        $this->assertSame('MOS LTSA', $workOrder->fungsi);
        $this->assertNull($workOrder->technician_coordinator_id);
        $this->assertTrue($workOrder->isUnclaimed());
        $this->assertSame('Corrective', $workOrder->classification);
        $this->assertSame($this->equipment->id, $workOrder->equipment_id);

        // Checklist dari aktivitas tipe equipment otomatis disalin
        $this->assertCount(2, $workOrder->workOrderActivities);
        $this->assertEqualsCanonicalizing(
            [$this->activityA->id, $this->activityB->id],
            $workOrder->workOrderActivities->pluck('activity_id')->all(),
        );
        $this->assertNull($workOrder->finish_at);
    }

    public function test_approve_requires_fungsi(): void
    {
        $request = $this->makeOpenRequest();

        $this->actingAs($this->spv);

        Livewire::test(ViewMaintenanceRequests::class, ['record' => $request->getRouteKey()])
            ->mountAction('approve')
            ->fillForm([
                'start_date' => now()->addDay()->toDateString(),
                'fungsi' => null,
                'classification' => 'Corrective',
                'activity_ids' => [],
            ])
            ->callMountedAction()
            ->assertHasActionErrors(['fungsi' => 'required']);

        $this->assertSame(MaintenanceRequest::STATUSES['Open'], $request->fresh()->status);
        $this->assertSame(0, WorkOrder::where('maintenance_request_id', $request->id)->count());
    }

    public function test_approve_fungsi_options_only_include_fungsi_with_mos_users(): void
    {
        $this->assertArrayHasKey('MOS LTSA', User::fungsiOptionsForRole('mos'));
        $this->assertArrayNotHasKey('MOS TAD', User::fungsiOptionsForRole('mos'));

        User::factory()->create(['fungsi' => 'MOS TAD'])->assignRole(Role::findOrCreate('mos', 'web'));

        $this->assertArrayHasKey('MOS TAD', User::fungsiOptionsForRole('mos'));
    }

    public function test_approve_can_select_specific_activities_only(): void
    {
        $request = $this->makeOpenRequest();

        $this->actingAs($this->spv);

        Livewire::test(ViewMaintenanceRequests::class, ['record' => $request->getRouteKey()])
            ->mountAction('approve')
            ->fillForm([
                'start_date' => now()->addDay()->toDateString(),
                'fungsi' => 'MOS LTSA',
                'classification' => 'Corrective',
                'activity_ids' => [$this->activityB->id],
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $workOrder = WorkOrder::where('maintenance_request_id', $request->id)->firstOrFail();

        $this->assertCount(1, $workOrder->workOrderActivities);
        $this->assertSame($this->activityB->id, $workOrder->workOrderActivities->first()->activity_id);
    }

    public function test_approve_without_activities_creates_work_order_with_empty_checklist(): void
    {
        $request = $this->makeOpenRequest();

        $this->actingAs($this->spv);

        Livewire::test(ViewMaintenanceRequests::class, ['record' => $request->getRouteKey()])
            ->mountAction('approve')
            ->fillForm([
                'start_date' => now()->addDay()->toDateString(),
                'fungsi' => 'MOS LTSA',
                'classification' => 'Corrective',
                'activity_ids' => [],
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $workOrder = WorkOrder::where('maintenance_request_id', $request->id)->firstOrFail();

        $this->assertCount(0, $workOrder->workOrderActivities);
    }

    public function test_approve_checklist_has_clear_all_action(): void
    {
        $request = $this->makeOpenRequest();

        $this->actingAs($this->spv);

        Livewire::test(ViewMaintenanceRequests::class, ['record' => $request->getRouteKey()])
            ->mountAction('approve')
            ->fillForm([
                'activity_ids' => [$this->activityA->id, $this->activityB->id],
            ])
            ->assertFormSet(
                ['activity_ids' => [$this->activityA->id, $this->activityB->id]],
                form: 'mountedActionSchema0',
            )
            ->callAction(
                TestAction::make('clearActivityChecklist')->schemaComponent('activity_ids'),
            )
            ->assertFormSet(
                fn (array $state) => $this->assertEmpty($state['activity_ids'] ?? []),
                form: 'mountedActionSchema0',
            );
    }

    public function test_approve_can_add_manual_activities_typed_by_user(): void
    {
        $request = $this->makeOpenRequest();

        $this->actingAs($this->spv);

        Livewire::test(ViewMaintenanceRequests::class, ['record' => $request->getRouteKey()])
            ->mountAction('approve')
            ->fillForm([
                'start_date' => now()->addDay()->toDateString(),
                'fungsi' => 'MOS LTSA',
                'classification' => 'Corrective',
                'activity_ids' => [],
                'manual_activities' => ['Cek manual bearing', '  Ganti seal  ', 'Cek manual bearing'],
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $workOrder = WorkOrder::where('maintenance_request_id', $request->id)->firstOrFail();
        $activities = $workOrder->workOrderActivities()->orderBy('id')->get();

        $this->assertCount(2, $activities);
        $this->assertSame(['Cek manual bearing', 'Ganti seal'], $activities->pluck('name')->all());
        $this->assertNull($activities->first()->activity_id);
        $this->assertSame('Cek manual bearing', $activities->first()->displayName());
    }

    public function test_approve_can_combine_checklist_and_manual_activities(): void
    {
        $request = $this->makeOpenRequest();

        $this->actingAs($this->spv);

        Livewire::test(ViewMaintenanceRequests::class, ['record' => $request->getRouteKey()])
            ->mountAction('approve')
            ->fillForm([
                'start_date' => now()->addDay()->toDateString(),
                'fungsi' => 'MOS LTSA',
                'classification' => 'Corrective',
                'activity_ids' => [$this->activityA->id],
                'manual_activities' => ['Pemeriksaan tambahan'],
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $workOrder = WorkOrder::where('maintenance_request_id', $request->id)->firstOrFail();
        $activities = $workOrder->workOrderActivities()->orderBy('id')->get();

        $this->assertCount(2, $activities);
        $this->assertSame($this->activityA->id, $activities->first()->activity_id);
        $this->assertSame($this->activityA->name, $activities->first()->name);
        $this->assertNull($activities->last()->activity_id);
        $this->assertSame('Pemeriksaan tambahan', $activities->last()->name);
    }

    public function test_reject_sets_status_rejected_without_work_order(): void
    {
        $request = $this->makeOpenRequest();

        $this->actingAs($this->spv);

        Livewire::test(ViewMaintenanceRequests::class, ['record' => $request->getRouteKey()])
            ->mountAction('reject')
            ->fillForm(['approval_notes' => 'Tidak perlu tindakan'])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame(MaintenanceRequest::STATUSES['Rejected'], $request->fresh()->status);
        $this->assertSame(0, WorkOrder::where('maintenance_request_id', $request->id)->count());
    }

    public function test_request_status_constants_cover_all_values_used_by_the_app(): void
    {
        $this->assertEqualsCanonicalizing(
            ['Open', 'Approved', 'Assigned', 'In Progress', 'Completed', 'Rejected'],
            array_keys(MaintenanceRequest::STATUSES),
        );
    }
}
