<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkOrderReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $technician;

    protected User $other;

    protected WorkOrder $workOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->technician = User::factory()->create(['name' => 'Teknisi A', 'fungsi' => 'MOS LTSA']);
        $this->other = User::factory()->create(['name' => 'User Lain', 'fungsi' => 'HSSE']);

        $company = Company::create(['code' => 'CO', 'name' => 'Demo']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Area']);
        $type = EquipmentType::create(['code' => 'pump', 'name' => 'Pump', 'fungsi' => 'MOS LTSA']);
        $equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
        ]);

        $this->workOrder = WorkOrder::create([
            'work_order_number' => 'WO-2026-0001',
            'equipment_id' => $equipment->id,
            'issued_by' => $this->technician->id,
            'technician_coordinator_id' => $this->technician->id,
            'fungsi' => 'MOS LTSA',
            'classification' => 'Preventive',
            'start_at' => now(),
            'finish_at' => now(),
            'status' => WorkOrder::STATUSES['Completed'],
        ]);

        WorkOrderActivity::create([
            'work_order_id' => $this->workOrder->id,
            'name' => 'Cek Pompa',
            'pre_inspection' => 'Good',
            'final_result' => 'Good',
            'executed' => true,
        ]);
    }

    public function test_completed_work_order_report_is_available_via_api(): void
    {
        Sanctum::actingAs($this->technician, ['*']);

        $response = $this->get("/api/work-orders/{$this->workOrder->id}/report");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_report_is_rejected_while_work_order_not_completed(): void
    {
        $this->workOrder->update(['status' => WorkOrder::STATUSES['In Progress']]);

        Sanctum::actingAs($this->technician, ['*']);

        $this->get("/api/work-orders/{$this->workOrder->id}/report")
            ->assertStatus(422);
    }

    public function test_unrelated_user_cannot_download_report(): void
    {
        Sanctum::actingAs($this->other, ['*']);

        $this->get("/api/work-orders/{$this->workOrder->id}/report")
            ->assertForbidden();
    }

    public function test_report_is_available_via_web_route_for_authenticated_user(): void
    {
        $this->actingAs($this->technician);

        $response = $this->get(route('work-orders.report.pdf', $this->workOrder));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
