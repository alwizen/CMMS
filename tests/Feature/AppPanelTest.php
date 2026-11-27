<?php

namespace Tests\Feature;

use App\Filament\App\Resources\WorkOrders\Pages\ViewWorkOrder;
use App\Filament\App\Resources\WorkOrders\RelationManagers\ActivitiesRelationManager;
use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingPlanTask;
use App\Models\MaintenanceRequest;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AppPanelTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Area $area;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        $this->company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $this->area = Area::create([
            'company_id' => $this->company->id,
            'code' => 'AR',
            'name' => 'Demo Area',
        ]);
        $type = EquipmentType::create(['code' => 'pump', 'name' => 'Pompa', 'fungsi' => 'MOS LTSA']);

        $this->equipment = Equipment::create([
            'area_id' => $this->area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
        ]);
    }

    protected function makeUser(string $role, ?string $fungsi = null): User
    {
        $user = User::factory()->create([
            'status' => true,
            'fungsi' => $fungsi,
        ]);
        $user->assignRole(Role::findOrCreate($role, 'web'));

        return $user;
    }

    protected function workOrder(User $coordinator, string $fungsi, string $number): WorkOrder
    {
        return WorkOrder::create([
            'work_order_number' => $number,
            'equipment_id' => $this->equipment->id,
            'issued_by' => $coordinator->id,
            'technician_coordinator_id' => $coordinator->id,
            'fungsi' => $fungsi,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['Pending'],
        ]);
    }

    public function test_mos_sees_only_scoped_work_orders_in_app_panel(): void
    {
        $mos = $this->makeUser('mos', 'MOS LTSA');
        $other = $this->makeUser('mos', 'HSSE');

        $own = $this->workOrder($mos, 'MOS LTSA', 'WO-OWN-001');
        $foreign = $this->workOrder($other, 'HSSE', 'WO-FOREIGN-001');

        $this->actingAs($mos)
            ->get('/app/work-orders')
            ->assertSuccessful()
            ->assertSee('WO-OWN-001')
            ->assertDontSee('WO-FOREIGN-001');

        $this->actingAs($mos)
            ->get("/app/work-orders/{$own->id}")
            ->assertSuccessful();

        $this->actingAs($mos)
            ->get("/app/work-orders/{$foreign->id}")
            ->assertNotFound();
    }

    public function test_mos_can_browse_equipment_and_create_maintenance_request(): void
    {
        $mos = $this->makeUser('mos', 'MOS LTSA');

        $this->actingAs($mos)
            ->get('/app/equipment')
            ->assertSuccessful()
            ->assertSee('P-101');

        $this->actingAs($mos)
            ->get('/app/maintenance-requests/create')
            ->assertSuccessful();

        $this->actingAs($mos)
            ->get('/app/maintenance-requests')
            ->assertSuccessful();
    }

    public function test_mos_sees_own_and_same_fungsi_requests_only(): void
    {
        $mos = $this->makeUser('mos', 'MOS LTSA');
        $sameFungsi = $this->makeUser('mos', 'MOS LTSA');
        $otherFungsi = $this->makeUser('mos', 'HSSE');

        $own = MaintenanceRequest::create([
            'equipment_id' => $this->equipment->id,
            'reported_by' => $mos->id,
            'operation_status' => 'Faulty',
            'description' => 'Request milik sendiri',
            'damage_date' => now(),
            'status' => MaintenanceRequest::STATUSES['Open'],
        ]);

        $shared = MaintenanceRequest::create([
            'equipment_id' => $this->equipment->id,
            'reported_by' => $sameFungsi->id,
            'operation_status' => 'Faulty',
            'description' => 'Request rekan satu fungsi',
            'damage_date' => now(),
            'status' => MaintenanceRequest::STATUSES['Open'],
        ]);

        // Request untuk equipment fungsi lain tidak boleh terlihat.
        $otherType = EquipmentType::create(['code' => 'panel', 'name' => 'Panel', 'fungsi' => 'HSSE']);
        $otherEquipment = Equipment::create([
            'area_id' => $this->area->id,
            'equipment_type_id' => $otherType->id,
            'tag_number' => 'PNL-101',
        ]);

        $foreign = MaintenanceRequest::create([
            'equipment_id' => $otherEquipment->id,
            'reported_by' => $otherFungsi->id,
            'operation_status' => 'Faulty',
            'description' => 'Request fungsi lain',
            'damage_date' => now(),
            'status' => MaintenanceRequest::STATUSES['Open'],
        ]);

        $this->actingAs($mos)
            ->get('/app/maintenance-requests')
            ->assertSuccessful()
            ->assertSee($own->request_number)
            ->assertSee($shared->request_number)
            ->assertDontSee($foreign->request_number);

        $this->actingAs($mos)
            ->get("/app/maintenance-requests/{$shared->id}")
            ->assertSuccessful();

        $this->actingAs($mos)
            ->get("/app/maintenance-requests/{$foreign->id}")
            ->assertNotFound();
    }

    public function test_request_is_visible_to_technician_holding_its_work_order(): void
    {
        $reporter = $this->makeUser('mos', 'MOS TAD');
        $technician = $this->makeUser('mos', 'MOS PM Reborn');

        $request = MaintenanceRequest::create([
            'equipment_id' => $this->equipment->id,
            'reported_by' => $reporter->id,
            'operation_status' => 'Faulty',
            'description' => 'Seal pompa bocor',
            'damage_date' => now(),
            'status' => MaintenanceRequest::STATUSES['In Progress'],
        ]);

        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'maintenance_request_id' => $request->id,
            'issued_by' => $reporter->id,
            'technician_coordinator_id' => $technician->id,
            'fungsi' => 'MOS PM Reborn',
            'classification' => 'Corrective',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);

        $this->assertTrue(UserVisibility::canAccessRequest($request, $technician));
        $this->assertContains(
            $request->id,
            UserVisibility::scopeRequests(MaintenanceRequest::query(), $technician)->pluck('id')->all(),
        );

        $this->actingAs($technician)
            ->get("/app/maintenance-requests/{$request->id}")
            ->assertSuccessful();
    }

    public function test_patra_jasa_only_sees_housekeeping_plans_of_supervised_area(): void
    {
        $patra = $this->makeUser('patra-jasa');
        $otherPatra = $this->makeUser('patra-jasa');

        $ownArea = HousekeepingArea::create([
            'company_id' => $this->company->id,
            'supervisor_id' => $patra->id,
            'code' => 'HK-1',
            'name' => 'Area Sendiri',
            'sla_days' => 1,
        ]);
        $foreignArea = HousekeepingArea::create([
            'company_id' => $this->company->id,
            'supervisor_id' => $otherPatra->id,
            'code' => 'HK-2',
            'name' => 'Area Orang Lain',
            'sla_days' => 1,
        ]);

        $ownPlan = HousekeepingPlan::create([
            'housekeeping_area_id' => $ownArea->id,
            'plan_date' => now(),
            'status' => HousekeepingPlan::STATUSES['Draft'],
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $ownPlan->id,
            'name' => 'Sapu Area Sendiri',
            'status' => 'Pending',
        ]);

        $foreignPlan = HousekeepingPlan::create([
            'housekeeping_area_id' => $foreignArea->id,
            'plan_date' => now(),
            'status' => HousekeepingPlan::STATUSES['Draft'],
        ]);
        HousekeepingPlanTask::create([
            'housekeeping_plan_id' => $foreignPlan->id,
            'name' => 'Sapu Area Lain',
            'status' => 'Pending',
        ]);

        $this->actingAs($patra)
            ->get('/app/housekeeping-plans')
            ->assertSuccessful()
            ->assertSee($ownPlan->plan_number)
            ->assertDontSee($foreignPlan->plan_number);

        $this->actingAs($patra)
            ->get("/app/housekeeping-plans/{$ownPlan->id}")
            ->assertSuccessful();

        $this->actingAs($patra)
            ->get("/app/housekeeping-plans/{$foreignPlan->id}")
            ->assertNotFound();

        $this->actingAs($patra)
            ->get('/app/housekeeping-report')
            ->assertSuccessful()
            ->assertSee('Sapu Area Sendiri')
            ->assertDontSee('Sapu Area Lain');
    }

    public function test_app_panel_sections_follow_shield_permissions(): void
    {
        $mos = $this->makeUser('mos', 'MOS LTSA');
        $patra = $this->makeUser('patra-jasa');

        $this->actingAs($mos)
            ->get('/app/housekeeping-report')
            ->assertForbidden();

        $this->actingAs($mos)
            ->get('/app/housekeeping-plans')
            ->assertForbidden();

        $this->actingAs($patra)
            ->get('/app/work-orders')
            ->assertForbidden();

        $this->actingAs($patra)
            ->get('/app/equipment')
            ->assertForbidden();

        $this->actingAs($patra)
            ->get('/app/maintenance-calendar')
            ->assertForbidden();
    }

    public function test_work_order_execution_follows_update_permission(): void
    {
        $mos = $this->makeUser('mos', 'MOS LTSA');
        $workOrder = $this->workOrder($mos, 'MOS LTSA', 'WO-EXEC-001');

        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAs($mos);

        Livewire::test(ViewWorkOrder::class, ['record' => $workOrder->id])
            ->assertActionVisible('start')
            ->assertActionVisible('complete');

        Role::findByName('mos', 'web')->revokePermissionTo('Update:WorkOrder');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($mos->fresh());

        Livewire::test(ViewWorkOrder::class, ['record' => $workOrder->id])
            ->assertActionHidden('start')
            ->assertActionHidden('complete');
    }

    public function test_other_user_only_sees_locked_work_order_in_app_panel(): void
    {
        $userA = $this->makeUser('mos', 'MOS PM Reborn');
        $userB = $this->makeUser('mos', 'MOS PM Reborn');

        $workOrder = $this->workOrder($userA, 'MOS PM Reborn', 'WO-LOCK-001');

        $this->actingAs($userA);
        $workOrder->update(['status' => WorkOrder::STATUSES['In Progress']]);

        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAs($userB->fresh());

        Livewire::test(ViewWorkOrder::class, ['record' => $workOrder->id])
            ->assertOk()
            ->assertSee('Sedang Dikerjakan')
            ->assertSee($userA->name)
            ->assertActionHidden('start')
            ->assertActionHidden('complete');
    }

    public function test_work_order_owner_keeps_execution_actions_in_app_panel(): void
    {
        $userA = $this->makeUser('mos', 'MOS PM Reborn');
        $userB = $this->makeUser('mos', 'MOS PM Reborn');

        $workOrder = $this->workOrder($userA, 'MOS PM Reborn', 'WO-LOCK-002');

        $this->actingAs($userA);
        $workOrder->update(['status' => WorkOrder::STATUSES['In Progress']]);

        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAs($userA->fresh());

        Livewire::test(ViewWorkOrder::class, ['record' => $workOrder->id])
            ->assertOk()
            ->assertActionVisible('complete')
            ->assertActionHidden('start');
    }

    public function test_checklist_can_be_filled_from_app_work_order_view_page(): void
    {
        $mos = $this->makeUser('mos', 'MOS PM Reborn');
        $workOrder = $this->workOrder($mos, 'MOS PM Reborn', 'WO-CHECK-001');
        // Checklist hanya bisa diisi setelah pekerjaan dimulai.
        $workOrder->update(['status' => WorkOrder::STATUSES['In Progress']]);

        $activity = WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'name' => 'Cek suhu',
            'executed' => false,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAs($mos);

        Livewire::test(ActivitiesRelationManager::class, [
            'ownerRecord' => $workOrder,
            'pageClass' => ViewWorkOrder::class,
        ])
            ->assertOk()
            ->assertSee('Cek suhu')
            ->assertTableActionVisible('edit', $activity);
    }

    public function test_app_checklist_table_shows_photo_count(): void
    {
        $mos = $this->makeUser('mos', 'MOS PM Reborn');
        $workOrder = $this->workOrder($mos, 'MOS PM Reborn', 'WO-PHOTO-001');

        WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'name' => 'Cek visual',
            'executed' => true,
            'photos' => ['work-order-activities/foto-1.jpeg'],
        ]);

        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAs($mos);

        Livewire::test(ActivitiesRelationManager::class, [
            'ownerRecord' => $workOrder,
            'pageClass' => ViewWorkOrder::class,
        ])
            ->assertOk()
            ->assertSee('1 foto')
            ->assertDontSee('0 foto');
    }

    public function test_photo_action_shows_modal_with_uploaded_photos(): void
    {
        $mos = $this->makeUser('mos', 'MOS PM Reborn');
        $workOrder = $this->workOrder($mos, 'MOS PM Reborn', 'WO-PHOTO-002');

        $withPhoto = WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'name' => 'Cek visual',
            'executed' => true,
            'photos' => ['work-order-activities/foto-1.jpeg'],
        ]);

        $withoutPhoto = WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'name' => 'Cek suhu',
            'executed' => false,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAs($mos);

        $component = Livewire::test(ActivitiesRelationManager::class, [
            'ownerRecord' => $workOrder,
            'pageClass' => ViewWorkOrder::class,
        ])
            ->assertOk()
            ->assertTableActionVisible('photos', $withPhoto)
            ->assertTableActionHidden('photos', $withoutPhoto)
            ->mountTableAction('photos', $withPhoto);

        $this->assertNotNull($component->instance()->getMountedAction());

        $html = view('filament.work-order-activity-photos', ['record' => $withPhoto->fresh()])->render();

        $this->assertStringContainsString('foto-1.jpeg', $html);
        $this->assertStringContainsString('1 foto', $html);
        $this->assertStringNotContainsString('Belum ada foto dokumentasi', $html);

        // Badge kolom foto terhubung ke action 'photos' (klik membuka modal).
        $column = $component->instance()->getTable()->getColumn('photos');

        $this->assertInstanceOf(Action::class, $column->getAction());
        $this->assertSame('photos', $column->getAction()->getName());
    }

    public function test_qualitative_checklist_form_uses_dropdown_options(): void
    {
        $mos = $this->makeUser('mos', 'MOS PM Reborn');
        $workOrder = $this->workOrder($mos, 'MOS PM Reborn', 'WO-CHECK-003');
        // Checklist hanya bisa diisi setelah pekerjaan dimulai.
        $workOrder->update(['status' => WorkOrder::STATUSES['In Progress']]);

        $type = $this->equipment->equipmentType;

        $master = Activity::create([
            'equipment_type_id' => $type->id,
            'name' => 'Cek visual',
            'type' => 'visual_check',
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'answer_type' => 'qualitative',
            'status' => true,
        ]);

        $activity = WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'activity_id' => $master->id,
            'name' => $master->name,
            'executed' => false,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAs($mos);

        Livewire::test(ActivitiesRelationManager::class, [
            'ownerRecord' => $workOrder,
            'pageClass' => ViewWorkOrder::class,
        ])
            ->mountTableAction('edit', $activity)
            ->fillForm([
                'pre_inspection' => 'Good',
                'final_result' => 'Fair',
            ])
            ->callMountedAction()
            ->assertHasNoTableActionErrors();

        $activity->refresh();

        $this->assertSame('Good', $activity->pre_inspection);
        $this->assertSame('Fair', $activity->final_result);
    }

    public function test_quantitative_checklist_form_keeps_free_text_result(): void
    {
        $mos = $this->makeUser('mos', 'MOS PM Reborn');
        $workOrder = $this->workOrder($mos, 'MOS PM Reborn', 'WO-CHECK-004');
        // Checklist hanya bisa diisi setelah pekerjaan dimulai.
        $workOrder->update(['status' => WorkOrder::STATUSES['In Progress']]);

        $type = $this->equipment->equipmentType;

        $master = Activity::create([
            'equipment_type_id' => $type->id,
            'name' => 'Ukur tekanan',
            'type' => 'measurement',
            'maintenance_classification' => 'preventive',
            'interval' => 'monthly',
            'answer_type' => 'quantitative',
            'unit' => 'bar',
            'status' => true,
        ]);

        $activity = WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'activity_id' => $master->id,
            'name' => $master->name,
            'executed' => false,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAs($mos);

        Livewire::test(ActivitiesRelationManager::class, [
            'ownerRecord' => $workOrder,
            'pageClass' => ViewWorkOrder::class,
        ])
            ->mountTableAction('edit', $activity)
            ->fillForm([
                'pre_inspection' => '4.2',
                'final_result' => '4.4',
            ])
            ->callMountedAction()
            ->assertHasNoTableActionErrors();

        $activity->refresh();

        $this->assertSame('4.2', $activity->pre_inspection);
        $this->assertSame('4.4', $activity->final_result);
    }

    public function test_checklist_is_hidden_for_user_locked_out_of_work_order(): void
    {
        $owner = $this->makeUser('mos', 'MOS PM Reborn');
        $other = $this->makeUser('mos', 'MOS PM Reborn');

        $workOrder = $this->workOrder($owner, 'MOS PM Reborn', 'WO-CHECK-002');
        $workOrder->update(['status' => WorkOrder::STATUSES['In Progress']]);

        $activity = WorkOrderActivity::create([
            'work_order_id' => $workOrder->id,
            'name' => 'Cek all',
            'executed' => false,
        ]);

        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAs($other->fresh());

        Livewire::test(ActivitiesRelationManager::class, [
            'ownerRecord' => $workOrder->fresh(),
            'pageClass' => ViewWorkOrder::class,
        ])
            ->assertOk()
            ->assertTableActionHidden('edit', $activity);
    }

    public function test_request_is_visible_to_users_of_same_fungsi(): void
    {
        $reporter = $this->makeUser('mos', 'MOS LTSA');
        $sameFungsi = $this->makeUser('mos', 'MOS LTSA');
        $otherFungsi = $this->makeUser('mos', 'HSSE');

        $request = MaintenanceRequest::create([
            'equipment_id' => $this->equipment->id,
            'reported_by' => $reporter->id,
            'operation_status' => 'Faulty',
            'description' => 'Seal pompa bocor',
            'damage_date' => now(),
            'status' => MaintenanceRequest::STATUSES['In Progress'],
        ]);

        $this->assertTrue(UserVisibility::canAccessRequest($request, $reporter));
        $this->assertTrue(UserVisibility::canAccessRequest($request, $sameFungsi));
        $this->assertFalse(UserVisibility::canAccessRequest($request, $otherFungsi));

        $visible = UserVisibility::scopeRequests(MaintenanceRequest::query(), $sameFungsi)
            ->pluck('id')
            ->all();

        $this->assertContains($request->id, $visible);
    }

    public function test_mos_can_view_documents_read_only_when_permitted(): void
    {
        $mos = $this->makeUser('mos', 'MOS LTSA');

        $document = Document::create([
            'title' => 'Manual Pompa P-101',
            'document_type' => 'user_manual',
            'equipment_id' => $this->equipment->id,
        ]);

        $this->actingAs($mos)
            ->get('/app/documents')
            ->assertSuccessful()
            ->assertSee($document->document_number)
            ->assertSee('Manual Pompa P-101');

        $this->actingAs($mos)
            ->get("/app/documents/{$document->id}")
            ->assertSuccessful()
            ->assertSee('Manual Pompa P-101');

        // Panel lapangan bersifat read-only untuk dokumen.
        $this->actingAs($mos)->get('/app/documents/create')->assertNotFound();
        $this->actingAs($mos)->get("/app/documents/{$document->id}/edit")->assertNotFound();
    }

    public function test_document_menu_follows_shield_permission(): void
    {
        $mos = $this->makeUser('mos', 'MOS LTSA');

        Role::findByName('mos', 'web')->revokePermissionTo([
            'ViewAny:Document',
            'View:Document',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($mos->fresh())
            ->get('/app/documents')
            ->assertForbidden();
    }

    public function test_document_permission_can_be_granted_to_other_roles(): void
    {
        $patra = $this->makeUser('patra-jasa');

        $this->actingAs($patra)
            ->get('/app/documents')
            ->assertForbidden();

        Role::findByName('patra-jasa', 'web')->givePermissionTo([
            'ViewAny:Document',
            'View:Document',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($patra->fresh())
            ->get('/app/documents')
            ->assertSuccessful();
    }

    public function test_field_report_pdf_is_role_guarded(): void
    {
        $mos = $this->makeUser('mos', 'MOS LTSA');
        $patra = $this->makeUser('patra-jasa');

        $this->actingAs($mos)
            ->get('/app/daily-report/pdf?date=2020-01-01')
            ->assertForbidden();

        $this->actingAs($patra)
            ->get('/app/daily-report/pdf?date=2020-01-01')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($patra)
            ->get('/admin/daily-report/pdf?date=2020-01-01')
            ->assertForbidden();
    }
}
