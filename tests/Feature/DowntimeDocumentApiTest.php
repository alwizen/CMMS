<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Company;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DowntimeDocumentApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $company = Company::create(['code' => 'CO', 'name' => 'Demo']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Area Demo']);
        $type = EquipmentType::create(['code' => 'pump', 'name' => 'Pompa']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
            'description' => 'Pompa transfer',
        ]);
    }

    protected function makeRequest(array $attributes = []): MaintenanceRequest
    {
        return MaintenanceRequest::create(array_merge([
            'equipment_id' => $this->equipment->id,
            'reported_by' => $this->user->id,
            'operation_status' => 'Stopped',
            'description' => 'Pompa berhenti',
            'damage_date' => today()->subDay()->toDateString(),
            'damage_time' => '08:00:00',
            'status' => MaintenanceRequest::STATUSES['In Progress'],
        ], $attributes));
    }

    public function test_downtime_endpoint_returns_corrective_request_with_duration(): void
    {
        $request = $this->makeRequest();

        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'maintenance_request_id' => $request->id,
            'issued_by' => $this->user->id,
            'classification' => 'Corrective',
            'start_at' => today()->setTime(9, 0),
            'finish_at' => today()->setTime(10, 0),
            'status' => WorkOrder::STATUSES['Completed'],
        ]);

        $response = $this->getJson('/api/downtime')->assertOk();

        $response->assertJsonPath('data.0.id', $request->id);
        $response->assertJsonPath('data.0.downtime.minutes', 26 * 60);
        $response->assertJsonPath('data.0.downtime.ongoing', false);
        $response->assertJsonPath('data.0.equipment.tag_number', 'P-101');
    }

    public function test_downtime_endpoint_excludes_preventive_and_rejected_requests(): void
    {
        $preventive = $this->makeRequest();

        WorkOrder::create([
            'equipment_id' => $this->equipment->id,
            'maintenance_request_id' => $preventive->id,
            'issued_by' => $this->user->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['Pending'],
        ]);

        $this->makeRequest([
            'status' => MaintenanceRequest::STATUSES['Rejected'],
            'description' => 'Ditolak',
        ]);

        $this->getJson('/api/downtime')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_downtime_endpoint_marks_ongoing_request(): void
    {
        $this->makeRequest([
            'status' => MaintenanceRequest::STATUSES['Approved'],
        ]);

        $this->getJson('/api/downtime')
            ->assertOk()
            ->assertJsonPath('data.0.downtime.ongoing', true)
            ->assertJsonPath('data.0.downtime.end', fn ($end) => filled($end));
    }

    public function test_documents_endpoint_filters_by_type_and_expiry_status(): void
    {
        Document::create([
            'title' => 'Sertifikat Pompa',
            'document_type' => 'certification',
            'equipment_id' => $this->equipment->id,
            'expiry_date' => today()->subDay(),
        ]);

        Document::create([
            'title' => 'Manual Pompa',
            'document_type' => 'user_manual',
        ]);

        $this->getJson('/api/documents?type=certification')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Sertifikat Pompa')
            ->assertJsonPath('data.0.expiry_status', 'expired');

        $this->getJson('/api/documents?expiry_status=no_expiry')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Manual Pompa');
    }

    public function test_documents_endpoint_searches_by_equipment_tag(): void
    {
        Document::create([
            'title' => 'P&ID Area',
            'document_type' => 'pnid',
            'equipment_id' => $this->equipment->id,
        ]);

        $this->getJson('/api/documents?search=P-101')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.equipment.tag_number', 'P-101');
    }

    public function test_document_show_returns_detail_and_404_for_missing_id(): void
    {
        $document = Document::create([
            'title' => 'Drawing Tangki',
            'document_type' => 'drawing',
        ]);

        $this->getJson("/api/documents/{$document->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Drawing Tangki')
            ->assertJsonPath('data.document_type_label', 'Drawing');

        $this->getJson('/api/documents/99999')->assertNotFound();
    }

    public function test_downtime_and_documents_require_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/downtime')
            ->assertUnauthorized();

        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/documents')
            ->assertUnauthorized();
    }
}
