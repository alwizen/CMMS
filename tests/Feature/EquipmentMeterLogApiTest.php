<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MeterLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EquipmentMeterLogApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Equipment $pump;

    protected Equipment $tank;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();

        $this->user = User::factory()->create();

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'LOAD', 'name' => 'Loading']);
        $pumpType = EquipmentType::create(['code' => 'pump', 'name' => 'Pompa']);
        $tankType = EquipmentType::create(['code' => 'tank', 'name' => 'Tangki']);

        $this->pump = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $pumpType->id,
            'tag_number' => 'P-101',
            'description' => 'Pompa transfer utama',
            'status' => 'active',
        ]);

        $this->tank = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $tankType->id,
            'tag_number' => 'T-201',
            'description' => 'Tangki premium',
            'status' => 'active',
        ]);
    }

    public function test_equipment_list_returns_area_and_type(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $data = $this->getJson('/api/equipment')->assertOk()->json('data');

        $this->assertCount(2, $data);
        $this->assertSame('P-101', $data[0]['tag_number']);
        $this->assertSame('Pompa', $data[0]['equipment_type']);
        $this->assertSame('Loading', $data[0]['area']['name']);
        $this->assertSame('Pompa transfer utama', $data[0]['description']);
    }

    public function test_equipment_search_matches_tag_description_and_type(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $byTag = $this->getJson('/api/equipment?search=P-101')->assertOk()->json('data');
        $this->assertCount(1, $byTag);
        $this->assertSame('P-101', $byTag[0]['tag_number']);

        $byDescription = $this->getJson('/api/equipment?search=premium')->assertOk()->json('data');
        $this->assertCount(1, $byDescription);
        $this->assertSame('T-201', $byDescription[0]['tag_number']);

        $byType = $this->getJson('/api/equipment?search=Pompa')->assertOk()->json('data');
        $this->assertCount(1, $byType);
        $this->assertSame('P-101', $byType[0]['tag_number']);
    }

    public function test_equipment_list_exposes_latest_meter_reading(): void
    {
        MeterLog::create([
            'equipment_id' => $this->pump->id,
            'reading_date' => '2026-10-01',
            'value' => 1200.0,
            'recorded_by' => $this->user->id,
        ]);

        MeterLog::create([
            'equipment_id' => $this->pump->id,
            'reading_date' => '2026-11-01',
            'value' => 1350.5,
            'recorded_by' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user, ['*']);

        $data = collect($this->getJson('/api/equipment')->assertOk()->json('data'));

        $pump = $data->firstWhere('tag_number', 'P-101');
        $tank = $data->firstWhere('tag_number', 'T-201');

        $this->assertSame(1350.5, $pump['latest_meter_log']['value']);
        $this->assertSame('2026-11-01', $pump['latest_meter_log']['reading_date']);
        $this->assertNull($tank['latest_meter_log']);
    }

    public function test_new_meter_log_becomes_latest_reading(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $this->postJson('/api/meter-logs', [
            'equipment_id' => $this->pump->id,
            'reading_date' => '2026-11-06',
            'value' => 17892.0,
        ])->assertCreated();

        $pump = collect($this->getJson('/api/equipment')->assertOk()->json('data'))
            ->firstWhere('tag_number', 'P-101');

        $this->assertEquals(17892.0, $pump['latest_meter_log']['value']);
        $this->assertSame('2026-11-06', $pump['latest_meter_log']['reading_date']);
    }

    public function test_equipment_detail_returns_metadata(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $data = $this->getJson("/api/equipment/{$this->pump->id}")->assertOk()->json('data');

        $this->assertSame('P-101', $data['tag_number']);
        $this->assertSame('Pompa', $data['equipment_type']);
        $this->assertSame('Loading', $data['area']['name']);
    }

    public function test_meter_log_list_is_paginated_and_filterable_by_equipment(): void
    {
        MeterLog::create([
            'equipment_id' => $this->pump->id,
            'reading_date' => today()->toDateString(),
            'value' => 1250.5,
            'recorded_by' => $this->user->id,
        ]);

        MeterLog::create([
            'equipment_id' => $this->tank->id,
            'reading_date' => today()->toDateString(),
            'value' => 320.0,
            'recorded_by' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user, ['*']);

        $all = $this->getJson('/api/meter-logs')->assertOk()->json('data');
        $this->assertCount(2, $all);

        $filtered = $this->getJson("/api/meter-logs?equipment_id={$this->pump->id}")->assertOk()->json('data');
        $this->assertCount(1, $filtered);
        $this->assertSame($this->pump->id, $filtered[0]['equipment_id']);
        $this->assertSame('P-101', $filtered[0]['equipment']['tag_number']);
    }

    public function test_meter_log_search_matches_equipment_tag(): void
    {
        MeterLog::create([
            'equipment_id' => $this->pump->id,
            'reading_date' => today()->toDateString(),
            'value' => 1250.5,
            'recorded_by' => $this->user->id,
        ]);

        MeterLog::create([
            'equipment_id' => $this->tank->id,
            'reading_date' => today()->toDateString(),
            'value' => 320.0,
            'recorded_by' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user, ['*']);

        $data = $this->getJson('/api/meter-logs?search=T-201')->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('T-201', $data[0]['equipment']['tag_number']);
    }

    public function test_meter_log_can_be_created_from_mobile(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $this->postJson('/api/meter-logs', [
            'equipment_id' => $this->pump->id,
            'reading_date' => '2026-11-06',
            'value' => 1500.75,
        ])->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('meter_logs', [
            'equipment_id' => $this->pump->id,
            'value' => 1500.75,
            'recorded_by' => $this->user->id,
        ]);
    }

    public function test_meter_log_requires_valid_payload(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $this->postJson('/api/meter-logs', [
            'equipment_id' => 999,
            'reading_date' => 'bukan-tanggal',
            'value' => -5,
        ])->assertStatus(422)->assertJsonValidationErrors(['equipment_id', 'reading_date', 'value']);
    }

    public function test_equipment_and_meter_log_require_authentication(): void
    {
        $this->getJson('/api/equipment')->assertStatus(401);
        $this->getJson('/api/meter-logs')->assertStatus(401);
    }
}
