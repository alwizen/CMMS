<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Models\WorkOrderFinding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkOrderFindingTest extends TestCase
{
    use RefreshDatabase;

    protected User $technician;

    protected WorkOrder $workOrder;

    protected EquipmentType $equipmentType;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'pengawas', 'spv_mos', 'mos'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->technician = User::factory()->create(['name' => 'Teknisi', 'fungsi' => 'MOS LTSA']);
        $this->technician->assignRole('mos');

        $company = Company::create(['code' => 'CO', 'name' => 'Demo']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Area']);
        $this->equipmentType = EquipmentType::create(['code' => 'pump', 'name' => 'Pump', 'fungsi' => 'MOS LTSA']);
        $equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $this->equipmentType->id,
            'tag_number' => 'P-101',
        ]);

        $this->workOrder = WorkOrder::create([
            'equipment_id' => $equipment->id,
            'issued_by' => $this->technician->id,
            'technician_coordinator_id' => $this->technician->id,
            'fungsi' => 'MOS LTSA',
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);
    }

    protected function makeActivity(array $attributes = []): WorkOrderActivity
    {
        return WorkOrderActivity::create(array_merge([
            'work_order_id' => $this->workOrder->id,
            'name' => 'Cek Pompa',
            'executed' => true,
            'final_result' => 'Good',
        ], $attributes));
    }

    public function test_qualitative_poor_creates_finding(): void
    {
        $activity = $this->makeActivity(['final_result' => 'Poor']);

        $finding = WorkOrderFinding::where('work_order_activity_id', $activity->id)->first();

        $this->assertNotNull($finding);
        $this->assertSame('Medium', $finding->severity);
        $this->assertSame(WorkOrderFinding::STATUSES['Open'], $finding->status);
        $this->assertSame('qualitative', $finding->answer_type);
        $this->assertNotNull($finding->finding_number);
    }

    public function test_not_working_is_high_severity(): void
    {
        $activity = $this->makeActivity(['final_result' => 'Not Working']);

        $finding = WorkOrderFinding::where('work_order_activity_id', $activity->id)->first();

        $this->assertNotNull($finding);
        $this->assertSame('High', $finding->severity);
    }

    public function test_good_result_does_not_create_finding(): void
    {
        $activity = $this->makeActivity(['final_result' => 'Good']);

        $this->assertNull(
            WorkOrderFinding::where('work_order_activity_id', $activity->id)->first(),
        );
    }

    public function test_quantitative_out_of_range_creates_finding(): void
    {
        $master = Activity::create([
            'equipment_type_id' => $this->equipmentType->id,
            'name' => 'Tekanan',
            'type' => 'measurement',
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'answer_type' => 'quantitative',
            'minimum' => '1',
            'maximum' => '5',
            'unit' => 'bar',
            'status' => true,
        ]);

        $activity = $this->makeActivity([
            'activity_id' => $master->id,
            'final_result' => '9',
            'unit' => 'bar',
        ]);

        $finding = WorkOrderFinding::where('work_order_activity_id', $activity->id)->first();

        $this->assertNotNull($finding);
        $this->assertSame('quantitative', $finding->answer_type);
        $this->assertStringContainsString('di luar batas', (string) $finding->description);
    }

    public function test_fixing_result_dismisses_open_finding(): void
    {
        $activity = $this->makeActivity(['final_result' => 'Poor']);

        $activity->update(['final_result' => 'Good']);

        $finding = WorkOrderFinding::where('work_order_activity_id', $activity->id)->first();

        $this->assertSame(WorkOrderFinding::STATUSES['Dismissed'], $finding->status);
    }

    public function test_technician_can_list_and_show_findings_via_api(): void
    {
        $this->makeActivity(['final_result' => 'Not Working']);

        Sanctum::actingAs($this->technician, ['*']);

        $this->getJson('/api/findings')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.severity', 'High');

        $finding = WorkOrderFinding::firstOrFail();

        $this->getJson("/api/findings/{$finding->id}")
            ->assertOk()
            ->assertJsonPath('data.finding_number', $finding->finding_number);
    }

    public function test_technician_can_submit_repair_request_from_finding(): void
    {
        $this->makeActivity(['final_result' => 'Not Working']);

        $finding = WorkOrderFinding::firstOrFail();

        Sanctum::actingAs($this->technician, ['*']);

        $response = $this->postJson("/api/findings/{$finding->id}/request");

        $response->assertCreated()
            ->assertJsonPath('data.status', WorkOrderFinding::STATUSES['Requested']);

        $finding->refresh();

        $this->assertSame(WorkOrderFinding::STATUSES['Requested'], $finding->status);
        $this->assertNotNull($finding->maintenance_request_id);
        $this->assertDatabaseHas('maintenance_requests', [
            'id' => $finding->maintenance_request_id,
            'equipment_id' => $finding->equipment_id,
            'status' => 'Open',
        ]);
    }

    public function test_unrelated_user_cannot_submit_repair_request(): void
    {
        $this->makeActivity(['final_result' => 'Poor']);

        $finding = WorkOrderFinding::firstOrFail();

        $other = User::factory()->create(['fungsi' => 'HSSE']);
        $other->assignRole('mos');

        Sanctum::actingAs($other, ['*']);

        $this->postJson("/api/findings/{$finding->id}/request")->assertForbidden();
    }

    public function test_finding_is_resolved_when_corrective_work_order_is_approved(): void
    {
        $this->makeActivity(['final_result' => 'Not Working']);
        $finding = WorkOrderFinding::firstOrFail();

        $maintenanceRequest = MaintenanceRequest::create([
            'equipment_id' => $finding->equipment_id,
            'reported_by' => $this->technician->id,
            'operation_status' => 'Faulty',
            'description' => 'Perbaikan',
            'damage_date' => now()->toDateString(),
            'status' => 'Approved',
        ]);

        $finding->update([
            'status' => WorkOrderFinding::STATUSES['Requested'],
            'maintenance_request_id' => $maintenanceRequest->id,
        ]);

        $corrective = WorkOrder::create([
            'equipment_id' => $finding->equipment_id,
            'maintenance_request_id' => $maintenanceRequest->id,
            'issued_by' => $this->technician->id,
            'classification' => 'Corrective',
            'start_at' => now(),
            'finish_at' => now(),
            'status' => WorkOrder::STATUSES['Completed'],
        ]);

        $corrective->update([
            'approval_status' => WorkOrder::APPROVAL_APPROVED,
            'approved_by' => $this->technician->id,
            'approved_at' => now(),
        ]);

        $finding->refresh();

        $this->assertSame(WorkOrderFinding::STATUSES['Resolved'], $finding->status);
        $this->assertSame($corrective->id, $finding->resolution_work_order_id);
        $this->assertNotNull($finding->resolved_at);
    }
}
