<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Support\UserVisibility;
use App\Support\WorkOrderGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkOrderSharedPoolTest extends TestCase
{
    use RefreshDatabase;

    protected User $ltsaA;

    protected User $ltsaB;

    protected User $hsse;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();
        Carbon::setTestNow(Carbon::parse('2026-11-16 08:00:00'));

        $this->ltsaA = User::factory()->create(['name' => 'LTSA A', 'fungsi' => 'MOS LTSA']);
        $this->ltsaB = User::factory()->create(['name' => 'LTSA B', 'fungsi' => 'MOS LTSA']);
        $this->hsse = User::factory()->create(['name' => 'HSSE User', 'fungsi' => 'HSSE']);

        $company = Company::create(['code' => 'CO', 'name' => 'Demo']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Area']);
        $type = EquipmentType::create(['code' => 'pump', 'name' => 'Pump', 'fungsi' => 'MOS LTSA']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
        ]);

        Activity::create([
            'equipment_type_id' => $type->id,
            'name' => 'Cek',
            'type' => 'maintenance',
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'answer_type' => 'qualitative',
            'status' => true,
        ]);
    }

    protected function generate(): WorkOrder
    {
        app(WorkOrderGenerator::class)->generateDirect(
            equipmentIds: [$this->equipment->id],
            interval: 'monthly',
            quantity: 1,
            startTime: '08:00',
            skipWeekend: false,
            issuedBy: $this->ltsaA->id,
            fungsi: 'MOS LTSA',
        );

        return WorkOrder::firstOrFail();
    }

    public function test_generated_work_order_is_owned_by_fungsi_and_unclaimed(): void
    {
        $wo = $this->generate();

        $this->assertSame('MOS LTSA', $wo->fungsi);
        $this->assertNull($wo->technician_coordinator_id);
        $this->assertTrue($wo->isUnclaimed());
    }

    public function test_all_users_of_same_fungsi_can_see_the_work_order(): void
    {
        $wo = $this->generate();

        foreach ([$this->ltsaA, $this->ltsaB] as $user) {
            $visible = UserVisibility::scopeWorkOrders(WorkOrder::query(), $user)->pluck('id')->all();

            $this->assertContains($wo->id, $visible);
            $this->assertTrue(UserVisibility::canAccessWorkOrder($wo, $user));
        }

        // User fungsi lain tidak melihat WO ini.
        $this->assertFalse(UserVisibility::canAccessWorkOrder($wo, $this->hsse));
        $this->assertNotContains(
            $wo->id,
            UserVisibility::scopeWorkOrders(WorkOrder::query(), $this->hsse)->pluck('id')->all(),
        );
    }

    public function test_first_user_starting_the_work_order_becomes_performed_by(): void
    {
        $wo = $this->generate();

        $this->actingAs($this->ltsaB);

        $wo->update(['status' => WorkOrder::STATUSES['In Progress']]);

        $wo->refresh();

        $this->assertSame($this->ltsaB->id, $wo->technician_coordinator_id);

        // User lain yang menyelesaikan setelahnya tidak menimpa performed by.
        $this->actingAs($this->ltsaA);
        $wo->update(['status' => WorkOrder::STATUSES['Completed']]);

        $this->assertSame($this->ltsaB->id, $wo->refresh()->technician_coordinator_id);
    }

    public function test_claim_is_not_overwritten_by_other_user(): void
    {
        $wo = $this->generate();

        $wo->claimBy($this->ltsaA->id);
        $wo->claimBy($this->ltsaB->id);

        $this->assertSame($this->ltsaA->id, $wo->refresh()->technician_coordinator_id);
    }

    public function test_api_activity_execution_claims_unclaimed_work_order(): void
    {
        $wo = $this->generate();

        $activity = WorkOrderActivity::create([
            'work_order_id' => $wo->id,
            'name' => 'Cek',
            'executed' => false,
        ]);

        Sanctum::actingAs($this->ltsaB, ['*']);

        $this->putJson("/api/work-orders/{$wo->id}/activities/{$activity->id}", [
            'executed' => true,
        ])->assertOk();

        $this->assertSame($this->ltsaB->id, $wo->refresh()->technician_coordinator_id);
        $this->assertFalse($wo->isUnclaimed());
    }

    public function test_work_order_is_locked_for_other_users_once_started(): void
    {
        $wo = $this->generate();

        $this->actingAs($this->ltsaA);
        $wo->update(['status' => WorkOrder::STATUSES['In Progress']]);

        $wo->refresh();

        $this->assertTrue($wo->isLockedFor($this->ltsaB));
        $this->assertFalse($wo->isLockedFor($this->ltsaA));
        $this->assertSame('LTSA A', $wo->lockedByName());
        $this->assertStringContainsString('LTSA A', $wo->lockMessage());
    }

    public function test_locked_work_order_is_not_locked_once_completed(): void
    {
        $wo = $this->generate();

        $this->actingAs($this->ltsaA);
        $wo->update(['status' => WorkOrder::STATUSES['In Progress']]);
        $wo->update(['status' => WorkOrder::STATUSES['Completed']]);

        $this->assertFalse($wo->refresh()->isLockedFor($this->ltsaB));
    }

    public function test_other_user_cannot_update_locked_work_order_via_api(): void
    {
        $wo = $this->generate();

        $this->actingAs($this->ltsaA);
        $wo->update(['status' => WorkOrder::STATUSES['In Progress']]);

        Sanctum::actingAs($this->ltsaB, ['*']);

        $this->putJson("/api/work-orders/{$wo->id}", [
            'status' => WorkOrder::STATUSES['Completed'],
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.locked_by.name', 'LTSA A');

        $this->assertSame(WorkOrder::STATUSES['In Progress'], $wo->refresh()->status);
        $this->assertSame($this->ltsaA->id, $wo->technician_coordinator_id);
    }

    public function test_other_user_cannot_fill_checklist_of_locked_work_order_via_api(): void
    {
        $wo = $this->generate();

        $activity = WorkOrderActivity::create([
            'work_order_id' => $wo->id,
            'name' => 'Cek',
            'executed' => false,
        ]);

        $this->actingAs($this->ltsaA);
        $wo->update(['status' => WorkOrder::STATUSES['In Progress']]);

        Sanctum::actingAs($this->ltsaB, ['*']);

        $this->putJson("/api/work-orders/{$wo->id}/activities/{$activity->id}", [
            'executed' => true,
            'final_result' => 'Dicoba B',
        ])->assertForbidden();

        $this->assertFalse($activity->refresh()->executed);
        $this->assertNull($activity->final_result);
    }

    public function test_other_user_can_still_view_locked_work_order_via_api(): void
    {
        $wo = $this->generate();

        $this->actingAs($this->ltsaA);
        $wo->update(['status' => WorkOrder::STATUSES['In Progress']]);

        Sanctum::actingAs($this->ltsaB, ['*']);

        $this->getJson("/api/work-orders/{$wo->id}")
            ->assertOk()
            ->assertJsonPath('data.is_locked', true)
            ->assertJsonPath('data.can_execute', false)
            ->assertJsonPath('data.locked_by.name', 'LTSA A');
    }

    public function test_supervisor_can_still_manage_locked_work_order_via_api(): void
    {
        $wo = $this->generate();

        $this->actingAs($this->ltsaA);
        $wo->update(['status' => WorkOrder::STATUSES['In Progress']]);

        $supervisor = User::factory()->create();
        $supervisor->assignRole(Role::findOrCreate('spv_mos', 'web'));

        Sanctum::actingAs($supervisor, ['*']);

        $this->putJson("/api/work-orders/{$wo->id}", [
            'status' => WorkOrder::STATUSES['Completed'],
        ])->assertOk();

        $this->assertSame(WorkOrder::STATUSES['Completed'], $wo->refresh()->status);
    }

    public function test_on_hold_status_is_rejected_via_api(): void
    {
        $wo = $this->generate();

        Sanctum::actingAs($this->ltsaA, ['*']);

        $this->putJson("/api/work-orders/{$wo->id}", [
            'status' => 'On Hold',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertSame(WorkOrder::STATUSES['Pending'], $wo->refresh()->status);
    }

    public function test_generator_skips_when_fungsi_has_no_active_user(): void
    {
        $this->ltsaA->update(['fungsi' => null]);
        $this->ltsaB->update(['fungsi' => null, 'status' => false]);

        $result = app(WorkOrderGenerator::class)->generateDirect(
            equipmentIds: [$this->equipment->id],
            interval: 'monthly',
            quantity: 1,
            startTime: '08:00',
            skipWeekend: false,
            issuedBy: $this->hsse->id,
            fungsi: 'MOS LTSA',
        );

        $this->assertSame(0, $result['created']);
        $this->assertArrayHasKey('P-101', $result['skip_reasons']);
    }
}
