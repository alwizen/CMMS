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
use App\Models\WorkOrderWorker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkOrderPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected User $technician;

    protected User $stranger;

    protected WorkOrder $workOrder;

    protected WorkOrderActivity $activity;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();
        Storage::fake('public');

        foreach (['super_admin', 'pengawas', 'spv_mos', 'ltsa_tad'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->technician = User::factory()->create();
        $this->technician->assignRole('ltsa_tad');

        $this->stranger = User::factory()->create();
        $this->stranger->assignRole('ltsa_tad');

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'tank', 'name' => 'Tank']);

        $equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'T-201',
            'description' => 'Tangki premium',
        ]);

        $activity = Activity::create([
            'equipment_type_id' => $type->id,
            'name' => 'Pembersihan Internal Tangki',
            'type' => 'cleaning',
            'maintenance_classification' => 'preventive',
            'interval' => 'yearly',
            'answer_type' => 'qualitative',
            'status' => true,
        ]);

        $this->workOrder = WorkOrder::create([
            'equipment_id' => $equipment->id,
            'issued_by' => $this->technician->id,
            'technician_coordinator_id' => $this->technician->id,
            'classification' => 'Preventive',
            'start_at' => now(),
            'status' => WorkOrder::STATUSES['In Progress'],
        ]);

        WorkOrderWorker::create([
            'work_order_id' => $this->workOrder->id,
            'user_id' => $this->technician->id,
            'role' => 'Teknisi',
        ]);

        $this->activity = WorkOrderActivity::create([
            'work_order_id' => $this->workOrder->id,
            'activity_id' => $activity->id,
            'executed' => false,
        ]);
    }

    protected function photosUrl(): string
    {
        return "/api/work-orders/{$this->workOrder->id}/activities/{$this->activity->id}/photos";
    }

    public function test_technician_can_upload_up_to_three_photos(): void
    {
        Sanctum::actingAs($this->technician, ['*']);

        $this->postJson($this->photosUrl(), [
            'photos' => [
                UploadedFile::fake()->image('before.jpg'),
                UploadedFile::fake()->image('during.jpg'),
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $photos = $this->activity->fresh()->photos;

        $this->assertCount(2, $photos);

        foreach ($photos as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_upload_is_rejected_when_exceeding_three_photos(): void
    {
        Sanctum::actingAs($this->technician, ['*']);

        $this->postJson($this->photosUrl(), [
            'photos' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
            ],
        ])->assertOk();

        $this->postJson($this->photosUrl(), [
            'photos' => [UploadedFile::fake()->image('d.jpg')],
        ])->assertStatus(422);

        $this->assertCount(3, $this->activity->fresh()->photos);
    }

    public function test_partial_upload_respects_remaining_slots(): void
    {
        Sanctum::actingAs($this->technician, ['*']);

        $this->postJson($this->photosUrl(), [
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertOk();

        $this->postJson($this->photosUrl(), [
            'photos' => [UploadedFile::fake()->image('c.jpg'), UploadedFile::fake()->image('d.jpg')],
        ])->assertStatus(422);

        $this->assertCount(2, $this->activity->fresh()->photos);
    }

    public function test_unrelated_user_cannot_upload_photos(): void
    {
        Sanctum::actingAs($this->stranger, ['*']);

        $this->postJson($this->photosUrl(), [
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ])->assertForbidden();
    }

    public function test_non_image_file_is_rejected(): void
    {
        Sanctum::actingAs($this->technician, ['*']);

        $this->postJson($this->photosUrl(), [
            'photos' => [UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')],
        ])->assertStatus(422);
    }

    public function test_technician_can_delete_one_photo(): void
    {
        Sanctum::actingAs($this->technician, ['*']);

        $this->postJson($this->photosUrl(), [
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertOk();

        $photos = $this->activity->fresh()->photos;
        $removed = $photos[0];

        $this->deleteJson($this->photosUrl(), ['path' => $removed])->assertOk();

        $remaining = $this->activity->fresh()->photos;

        $this->assertCount(1, $remaining);
        $this->assertNotContains($removed, $remaining);
        Storage::disk('public')->assertMissing($removed);
    }

    public function test_delete_accepts_path_as_query_parameter(): void
    {
        Sanctum::actingAs($this->technician, ['*']);

        $this->postJson($this->photosUrl(), [
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ])->assertOk();

        $path = $this->activity->fresh()->photos[0];

        $this->deleteJson($this->photosUrl().'?path='.urlencode($path))->assertOk();

        $this->assertCount(0, $this->activity->fresh()->photos);
    }

    public function test_delete_rejects_unknown_path(): void
    {
        Sanctum::actingAs($this->technician, ['*']);

        $this->deleteJson($this->photosUrl(), ['path' => 'work-order-activities/ngawur.jpg'])
            ->assertStatus(404);
    }

    public function test_activity_resource_exposes_photos(): void
    {
        Sanctum::actingAs($this->technician, ['*']);

        $this->postJson($this->photosUrl(), [
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ])->assertOk()->assertJsonCount(1, 'data.photos');
    }

    public function test_photos_remain_after_work_order_completed(): void
    {
        Sanctum::actingAs($this->technician, ['*']);

        $this->postJson($this->photosUrl(), [
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ])->assertOk();

        $this->workOrder->update([
            'status' => WorkOrder::STATUSES['Completed'],
            'finish_at' => now(),
        ]);

        $this->assertCount(1, $this->activity->fresh()->photos);
    }
}
