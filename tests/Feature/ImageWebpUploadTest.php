<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\HousekeepingArea;
use App\Models\Setting;
use App\Models\User;
use App\Support\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImageWebpUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Equipment $equipment;

    protected HousekeepingArea $housekeepingArea;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        foreach (['super_admin', 'technician'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole('technician');
        Sanctum::actingAs($this->user, ['*']);

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'AR', 'name' => 'Demo Area']);
        $type = EquipmentType::create(['code' => 'pompa', 'name' => 'Pompa']);

        $this->equipment = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $type->id,
            'tag_number' => 'P-101',
            'description' => 'Pompa utama',
        ]);

        $this->housekeepingArea = HousekeepingArea::create([
            'company_id' => $company->id,
            'code' => 'taman',
            'name' => 'Taman Kantor',
            'is_active' => true,
        ]);
    }

    public function test_maintenance_request_photo_is_stored_as_webp(): void
    {
        Storage::fake('public');
        config(['media.disk' => 'local']);

        $response = $this->post('/api/maintenance-requests', [
            'equipment_id' => $this->equipment->id,
            'operation_status' => 'Faulty',
            'description' => 'Seal bocor',
            'damage_date' => today()->toDateString(),
            'photo' => [UploadedFile::fake()->image('foto.jpg', 2400, 1600)],
        ], ['Accept' => 'application/json'])->assertCreated();

        $photo = $response->json('data.photo.0');
        $path = Media::pathFromUrl($photo);

        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);

        $info = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame('image/webp', $info['mime']);
    }

    public function test_housekeeping_request_photo_is_stored_as_webp(): void
    {
        Storage::fake('public');
        config(['media.disk' => 'local']);

        $response = $this->post('/api/housekeeping-requests', [
            'housekeeping_area_id' => $this->housekeepingArea->id,
            'request_date' => today()->toDateString(),
            'description' => 'Taman kotor',
            'photos' => [UploadedFile::fake()->image('foto.jpg', 2400, 1600)],
        ], ['Accept' => 'application/json'])->assertCreated();

        $photo = $response->json('data.photo.0');
        $path = Media::pathFromUrl($photo);

        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);

        $info = getimagesizefromstring(Storage::disk('public')->get($path));
        $this->assertSame('image/webp', $info['mime']);
    }

    public function test_upload_goes_to_r2_disk_when_media_disk_is_r2(): void
    {
        Storage::fake('public');
        Storage::fake('r2');
        config(['media.disk' => 'r2']);

        $response = $this->post('/api/maintenance-requests', [
            'equipment_id' => $this->equipment->id,
            'operation_status' => 'Faulty',
            'description' => 'Seal bocor',
            'damage_date' => today()->toDateString(),
            'photo' => [UploadedFile::fake()->image('foto.jpg')],
        ], ['Accept' => 'application/json'])->assertCreated();

        $photo = $response->json('data.photo.0');
        $path = Media::pathFromUrl($photo);

        Storage::disk('r2')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }
}
