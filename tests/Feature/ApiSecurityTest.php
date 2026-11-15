<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Company;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceRequest;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $other;

    protected User $admin;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        foreach (['super_admin', 'technician'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->owner = User::factory()->create();
        $this->owner->assignRole('technician');

        $this->other = User::factory()->create();
        $this->other->assignRole('technician');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'pompa', 'name' => 'Pompa']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
            'description' => 'Pompa utama',
        ]);
    }

    protected function makeRequest(array $attributes = []): MaintenanceRequest
    {
        return MaintenanceRequest::create(array_merge([
            'equipment_id' => $this->equipment->id,
            'reported_by' => $this->owner->id,
            'operation_status' => 'Faulty',
            'description' => 'Seal bocor',
            'damage_date' => today()->toDateString(),
            'status' => MaintenanceRequest::STATUSES['Open'],
        ], $attributes));
    }

    public function test_other_user_cannot_view_or_edit_someone_elses_request(): void
    {
        $request = $this->makeRequest();

        Sanctum::actingAs($this->other, ['*']);

        $this->getJson("/api/maintenance-requests/{$request->id}")->assertForbidden();
        $this->putJson("/api/maintenance-requests/{$request->id}", ['description' => 'diretas'])
            ->assertForbidden();
        $this->deleteJson("/api/maintenance-requests/{$request->id}")->assertForbidden();
    }

    public function test_owner_can_view_and_edit_their_request(): void
    {
        $request = $this->makeRequest();

        Sanctum::actingAs($this->owner, ['*']);

        $this->getJson("/api/maintenance-requests/{$request->id}")->assertOk();
        $this->putJson("/api/maintenance-requests/{$request->id}", ['description' => 'Diperbarui'])
            ->assertOk();
    }

    public function test_full_access_role_can_view_any_request(): void
    {
        $request = $this->makeRequest();

        Sanctum::actingAs($this->admin, ['*']);

        $this->getJson("/api/maintenance-requests/{$request->id}")->assertOk();
    }

    public function test_store_always_creates_open_status_ignoring_client_status(): void
    {
        Sanctum::actingAs($this->owner, ['*']);

        $this->postJson('/api/maintenance-requests', [
            'equipment_id' => $this->equipment->id,
            'operation_status' => 'Faulty',
            'description' => 'Klaim approved dari klien',
            'damage_date' => today()->toDateString(),
            'status' => MaintenanceRequest::STATUSES['Approved'],
        ])->assertCreated()->assertJsonPath('data.status', MaintenanceRequest::STATUSES['Open']);
    }

    public function test_status_cannot_be_changed_by_non_privileged_user(): void
    {
        $request = $this->makeRequest();

        Sanctum::actingAs($this->owner, ['*']);

        $this->putJson("/api/maintenance-requests/{$request->id}", [
            'status' => MaintenanceRequest::STATUSES['Approved'],
        ])->assertForbidden();

        $this->assertSame(MaintenanceRequest::STATUSES['Open'], $request->fresh()->status);
    }

    public function test_privileged_user_must_follow_status_transitions(): void
    {
        $request = $this->makeRequest();

        Sanctum::actingAs($this->admin, ['*']);

        $this->putJson("/api/maintenance-requests/{$request->id}", [
            'status' => MaintenanceRequest::STATUSES['Completed'],
        ])->assertStatus(422);

        $this->putJson("/api/maintenance-requests/{$request->id}", [
            'status' => MaintenanceRequest::STATUSES['Approved'],
        ])->assertOk();

        $this->assertSame(MaintenanceRequest::STATUSES['Approved'], $request->fresh()->status);
        $this->assertSame($this->admin->id, $request->fresh()->approved_by);
    }

    public function test_other_user_cannot_view_work_order_they_are_not_part_of(): void
    {
        $workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->owner->id,
            'technician_coordinator_id' => $this->owner->id,
            'classification' => 'Corrective',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['Pending'],
        ]);

        Sanctum::actingAs($this->other, ['*']);

        $this->getJson("/api/work-orders/{$workOrder->id}")->assertForbidden();
    }

    public function test_linked_worker_can_view_work_order(): void
    {
        $workOrder = WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'issued_by' => $this->owner->id,
            'technician_coordinator_id' => $this->owner->id,
            'classification' => 'Corrective',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['Pending'],
        ]);

        WorkOrderWorker::create([
            'work_order_id' => $workOrder->id,
            'user_id' => $this->other->id,
            'role' => 'Teknisi',
        ]);

        Sanctum::actingAs($this->other, ['*']);

        $this->getJson("/api/work-orders/{$workOrder->id}")->assertOk();
    }

    public function test_per_page_is_capped_at_one_hundred(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/equipment?per_page=5000')->assertOk();

        $this->assertSame(100, $response->json('meta.per_page'));
    }

    public function test_document_file_requires_valid_signature(): void
    {
        Storage::fake('local');

        $document = Document::create([
            'title' => 'Sertifikat',
            'document_type' => 'certification',
            'file_path' => 'documents/sertifikat.pdf',
        ]);

        Storage::disk('local')->put('documents/sertifikat.pdf', 'isi');

        $this->getJson("/api/documents/{$document->id}/file")->assertForbidden();

        $signed = URL::temporarySignedRoute('documents.file', now()->addMinutes(5), ['document' => $document->id]);

        $this->get($signed)->assertOk();
    }

    public function test_document_resource_uses_signed_url(): void
    {
        $document = Document::create([
            'title' => 'Sertifikat',
            'document_type' => 'certification',
            'file_path' => 'documents/sertifikat.pdf',
        ]);

        Sanctum::actingAs($this->owner, ['*']);

        $url = $this->getJson("/api/documents/{$document->id}")->assertOk()->json('data.file_url');

        $this->assertIsString($url);
        $this->assertStringContainsString('signature=', $url);
    }
}
