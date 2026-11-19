<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingManpower;
use App\Models\HousekeepingPlan;
use App\Models\HousekeepingRequest;
use App\Models\HousekeepingTask;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiHousekeepingRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected HousekeepingArea $area;

    protected HousekeepingFacility $facility;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);

        $this->area = HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'taman',
            'name' => 'Taman Kantor',
            'is_active' => true,
        ]);

        $this->facility = HousekeepingFacility::create([
            'housekeeping_area_id' => $this->area->id,
            'code' => 'gazebo',
            'name' => 'Gazebo',
            'is_active' => true,
        ]);

        HousekeepingTask::create([
            'housekeeping_facility_id' => $this->facility->id,
            'name' => 'Sapu Daun',
            'is_active' => true,
        ]);
    }

    public function test_store_creates_request_with_generated_number(): void
    {
        $response = $this->postJson('/api/housekeeping-requests', [
            'housekeeping_area_id' => $this->area->id,
            'housekeeping_facility_id' => $this->facility->id,
            'request_date' => today()->toDateString(),
            'description' => 'Gazebo kotor',
            'priority' => 'High',
        ])->assertCreated();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'Open')
            ->assertJsonPath('data.priority', 'High')
            ->assertJsonPath('data.area.name', 'Taman Kantor');

        $this->assertStringStartsWith('HKR-', HousekeepingRequest::firstOrFail()->request_number);
    }

    public function test_index_filters_by_status_and_search(): void
    {
        HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'request_date' => today()->toDateString(),
            'description' => 'Gazebo kotor',
            'status' => HousekeepingRequest::STATUSES['Open'],
        ]);
        HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'request_date' => today()->toDateString(),
            'description' => 'Taman perlu pemangkasan',
            'status' => HousekeepingRequest::STATUSES['Approved'],
        ]);

        $this->getJson('/api/housekeeping-requests')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);

        $this->getJson('/api/housekeeping-requests?status=Open')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Gazebo kotor');

        $this->getJson('/api/housekeeping-requests?search=pemangkasan')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_approve_creates_plan_and_links_request(): void
    {
        $manpower = HousekeepingManpower::create([
            'housekeeping_area_id' => $this->area->id,
            'name' => 'Budi',
            'is_active' => true,
        ]);

        $request = HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'housekeeping_facility_id' => $this->facility->id,
            'request_date' => today()->toDateString(),
            'description' => 'Gazebo kotor',
            'status' => HousekeepingRequest::STATUSES['Open'],
        ]);

        $response = $this->postJson("/api/housekeeping-requests/{$request->id}/approve", [
            'plan_date' => today()->toDateString(),
            'manpower_ids' => [$manpower->id],
            'approval_notes' => 'Disetujui',
        ])->assertOk();

        $response->assertJsonPath('data.status', 'Approved')
            ->assertJsonPath('data.plan.id', HousekeepingPlan::firstOrFail()->id)
            ->assertJsonPath('plan.task_count', 1)
            ->assertJsonPath('plan.manpower_count', 1);

        $this->assertSame(1, HousekeepingPlan::count());
    }

    public function test_approve_rejects_non_open_request(): void
    {
        $request = HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'request_date' => today()->toDateString(),
            'description' => 'Sudah diproses',
            'status' => HousekeepingRequest::STATUSES['Rejected'],
        ]);

        $this->postJson("/api/housekeeping-requests/{$request->id}/approve")
            ->assertStatus(422);

        $this->assertSame(0, HousekeepingPlan::count());
    }

    public function test_reject_requires_notes_and_sets_status(): void
    {
        $request = HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'request_date' => today()->toDateString(),
            'description' => 'Bukan prioritas',
            'status' => HousekeepingRequest::STATUSES['Open'],
        ]);

        $this->postJson("/api/housekeeping-requests/{$request->id}/reject", [])
            ->assertStatus(422);

        $this->postJson("/api/housekeeping-requests/{$request->id}/reject", [
            'approval_notes' => 'Tidak masuk anggaran',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'Rejected')
            ->assertJsonPath('data.approval_notes', 'Tidak masuk anggaran');
    }

    public function test_housekeeping_requests_require_authentication(): void
    {
        $this->app['auth']->forgetGuards();

        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/housekeeping-requests')
            ->assertUnauthorized();
    }

    public function test_store_accepts_multipart_photo_upload(): void
    {
        Storage::fake('public');

        $response = $this->post('/api/housekeeping-requests', [
            'housekeeping_area_id' => $this->area->id,
            'request_date' => today()->toDateString(),
            'description' => 'Gazebo kotor dengan foto',
            'photos' => [UploadedFile::fake()->image('foto.jpg')],
        ], ['Accept' => 'application/json'])->assertCreated();

        $response->assertJsonCount(1, 'data.photo');

        $photo = $response->json('data.photo.0');
        Storage::disk('public')->assertExists($photo);
    }

    public function test_index_supports_mine_filter(): void
    {
        $other = User::factory()->create();

        HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'request_date' => today()->toDateString(),
            'description' => 'Request saya',
            'requested_by' => $this->user->id,
            'status' => HousekeepingRequest::STATUSES['Open'],
        ]);
        HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'request_date' => today()->toDateString(),
            'description' => 'Request orang lain',
            'requested_by' => $other->id,
            'status' => HousekeepingRequest::STATUSES['Open'],
        ]);

        $this->getJson('/api/housekeeping-requests?mine=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Request saya');
    }

    public function test_owner_can_update_open_request(): void
    {
        $request = HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'request_date' => today()->toDateString(),
            'description' => 'Deskripsi lama',
            'requested_by' => $this->user->id,
            'status' => HousekeepingRequest::STATUSES['Open'],
        ]);

        $this->putJson("/api/housekeeping-requests/{$request->id}", [
            'description' => 'Deskripsi baru',
            'priority' => 'High',
        ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Deskripsi baru')
            ->assertJsonPath('data.priority', 'High');
    }

    public function test_other_user_cannot_update_or_delete_request(): void
    {
        $request = HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'request_date' => today()->toDateString(),
            'description' => 'Request orang lain',
            'requested_by' => User::factory()->create()->id,
            'status' => HousekeepingRequest::STATUSES['Open'],
        ]);

        $this->putJson("/api/housekeeping-requests/{$request->id}", ['description' => 'Diretas'])
            ->assertForbidden();
        $this->deleteJson("/api/housekeeping-requests/{$request->id}")->assertForbidden();
    }

    public function test_cannot_update_approved_request(): void
    {
        $request = HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'request_date' => today()->toDateString(),
            'description' => 'Sudah disetujui',
            'requested_by' => $this->user->id,
            'status' => HousekeepingRequest::STATUSES['Approved'],
        ]);

        $this->putJson("/api/housekeeping-requests/{$request->id}", ['description' => 'Ubah'])
            ->assertStatus(422);
    }

    public function test_owner_can_delete_open_request_and_photos_are_removed(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('foto.jpg')->store('housekeeping/requests', 'public');

        $request = HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'request_date' => today()->toDateString(),
            'description' => 'Akan dihapus',
            'requested_by' => $this->user->id,
            'status' => HousekeepingRequest::STATUSES['Open'],
            'photo' => [$photo],
        ]);

        $this->deleteJson("/api/housekeeping-requests/{$request->id}")->assertOk();

        $this->assertDatabaseMissing('housekeeping_requests', ['id' => $request->id]);
        Storage::disk('public')->assertMissing($photo);
    }

    public function test_upload_and_delete_request_photos(): void
    {
        Storage::fake('public');

        $request = HousekeepingRequest::create([
            'housekeeping_area_id' => $this->area->id,
            'request_date' => today()->toDateString(),
            'description' => 'Dengan foto',
            'requested_by' => $this->user->id,
            'status' => HousekeepingRequest::STATUSES['Open'],
        ]);

        $response = $this->post("/api/housekeeping-requests/{$request->id}/photos", [
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();

        $path = $response->json('data.photo.0');
        Storage::disk('public')->assertExists($path);

        $this->deleteJson("/api/housekeeping-requests/{$request->id}/photos", ['path' => $path])
            ->assertOk()
            ->assertJsonCount(0, 'data.photo');

        Storage::disk('public')->assertMissing($path);
    }
}
