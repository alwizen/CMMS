<?php

namespace Tests\Feature;

use App\Filament\Imports\ActivityImporter;
use App\Filament\Imports\AreaImporter;
use App\Filament\Imports\EquipmentImporter;
use App\Filament\Imports\EquipmentTypeImporter;
use App\Models\Activity;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\User;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ImporterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Area $area;

    protected EquipmentType $pumpType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->company = Company::create([
            'code' => 'FT-TGL',
            'name' => 'FT Tegal',
        ]);

        $this->area = Area::create([
            'company_id' => $this->company->id,
            'code' => 'LOAD',
            'name' => 'Loading',
        ]);

        $this->pumpType = EquipmentType::create([
            'code' => 'pump',
            'name' => 'Pompa',
        ]);
    }

    /**
     * @param  class-string  $importerClass
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function runImport(string $importerClass, array $rows): void
    {
        $columnMap = collect($importerClass::getColumns())
            ->mapWithKeys(fn ($column): array => [$column->getName() => $column->getName()])
            ->all();

        $import = Import::create([
            'file_name' => 'test.csv',
            'file_path' => 'imports/test.csv',
            'importer' => $importerClass,
            'total_rows' => count($rows),
            'successful_rows' => 0,
            'user_id' => $this->user->id,
        ]);

        $importer = $import->getImporter($columnMap, []);

        foreach ($rows as $row) {
            $importer($row);
        }
    }

    public function test_area_import_resolves_company_by_name_code_and_id(): void
    {
        $this->runImport(AreaImporter::class, [
            ['company_id' => 'FT Tegal', 'code' => 'STORE', 'name' => 'Storage'],
            ['company_id' => 'FT-TGL', 'code' => 'UTIL', 'name' => 'Utility'],
            ['company_id' => (string) $this->company->id, 'code' => 'MCC', 'name' => 'MCC'],
        ]);

        $this->assertDatabaseHas('areas', ['code' => 'STORE', 'company_id' => $this->company->id]);
        $this->assertDatabaseHas('areas', ['code' => 'UTIL', 'company_id' => $this->company->id]);
        $this->assertDatabaseHas('areas', ['code' => 'MCC', 'company_id' => $this->company->id]);
    }

    public function test_area_import_updates_existing_row_instead_of_duplicating(): void
    {
        $this->runImport(AreaImporter::class, [
            ['company_id' => 'FT-TGL', 'code' => 'LOAD', 'name' => 'Loading Baru', 'description' => 'Diperbarui'],
        ]);

        $this->assertSame(1, Area::where('company_id', $this->company->id)->where('code', 'LOAD')->count());
        $this->assertSame('Loading Baru', $this->area->fresh()->name);
        $this->assertSame('Diperbarui', $this->area->fresh()->description);
    }

    public function test_area_import_parses_boolean_variants(): void
    {
        $this->runImport(AreaImporter::class, [
            ['company_id' => 'FT-TGL', 'code' => 'A1', 'name' => 'A1', 'is_active' => 'inactive'],
            ['company_id' => 'FT-TGL', 'code' => 'A2', 'name' => 'A2', 'is_active' => '0'],
            ['company_id' => 'FT-TGL', 'code' => 'A3', 'name' => 'A3', 'is_active' => 'aktif'],
        ]);

        $this->assertFalse((bool) Area::where('code', 'A1')->value('is_active'));
        $this->assertFalse((bool) Area::where('code', 'A2')->value('is_active'));
        $this->assertTrue((bool) Area::where('code', 'A3')->value('is_active'));
    }

    public function test_area_import_stores_coordinates(): void
    {
        $this->runImport(AreaImporter::class, [
            [
                'company_id' => 'FT-TGL',
                'code' => 'GEO',
                'name' => 'Geo Area',
                'latitude' => '-6.8702557',
                'longitude' => '109.1867048',
            ],
        ]);

        $area = Area::where('code', 'GEO')->first();

        $this->assertSame(-6.8702557, $area->latitude);
        $this->assertSame(109.1867048, $area->longitude);
    }

    public function test_area_import_fails_when_company_is_unknown(): void
    {
        $this->expectException(ValidationException::class);

        $this->runImport(AreaImporter::class, [
            ['company_id' => 'PT Tidak Ada', 'code' => 'X1', 'name' => 'X1'],
        ]);
    }

    public function test_area_import_keeps_optional_values_when_blank_on_update(): void
    {
        $this->area->update([
            'description' => 'Deskripsi lama',
            'is_active' => false,
        ]);

        $this->runImport(AreaImporter::class, [
            ['company_id' => 'FT-TGL', 'code' => 'LOAD', 'name' => 'Loading'],
        ]);

        $area = $this->area->fresh();

        $this->assertSame('Deskripsi lama', $area->description);
        $this->assertFalse($area->is_active);
    }

    public function test_equipment_import_resolves_area_and_type_and_updates_by_tag_number(): void
    {
        $this->runImport(EquipmentImporter::class, [
            [
                'area_id' => 'LOAD',
                'tag_number' => 'P-101',
                'description' => 'Pompa transfer',
                'equipment_type_id' => 'Pompa',
                'status' => 'active',
                'criticality' => 'medium',
            ],
        ]);

        $equipment = Equipment::where('tag_number', 'P-101')->first();

        $this->assertNotNull($equipment);
        $this->assertSame($this->area->id, $equipment->area_id);
        $this->assertSame($this->pumpType->id, $equipment->equipment_type_id);

        $this->runImport(EquipmentImporter::class, [
            [
                'area_id' => 'Loading',
                'tag_number' => 'P-101',
                'description' => 'Pompa transfer utama',
                'equipment_type_id' => 'pump',
                'status' => 'active',
                'criticality' => 'high',
            ],
        ]);

        $this->assertSame(1, Equipment::where('tag_number', 'P-101')->count());
        $this->assertSame('Pompa transfer utama', $equipment->fresh()->description);
        $this->assertSame('high', $equipment->fresh()->criticality);
    }

    public function test_equipment_import_fails_when_equipment_type_is_unknown(): void
    {
        $this->expectException(ValidationException::class);

        $this->runImport(EquipmentImporter::class, [
            [
                'area_id' => 'LOAD',
                'tag_number' => 'P-999',
                'equipment_type_id' => 'Tipe Tidak Ada',
                'status' => 'active',
                'criticality' => 'medium',
            ],
        ]);
    }

    public function test_equipment_type_import_updates_by_code(): void
    {
        $this->runImport(EquipmentTypeImporter::class, [
            ['code' => 'pump', 'name' => 'Pompa Centrifugal', 'status' => 'inactive'],
        ]);

        $this->assertSame(1, EquipmentType::where('code', 'pump')->count());

        $type = $this->pumpType->fresh();

        $this->assertSame('Pompa Centrifugal', $type->name);
        $this->assertFalse($type->status);
    }

    public function test_activity_import_resolves_equipment_type_and_updates_composite_key(): void
    {
        $this->runImport(ActivityImporter::class, [
            [
                'equipment_type_id' => 'Pompa',
                'name' => 'Oil Change',
                'type' => 'Maintenance',
                'maintenance_classification' => 'Preventive',
                'interval' => 'Monthly',
                'answer_type' => 'Qualitative',
            ],
        ]);

        $activity = Activity::where('name', 'Oil Change')->first();

        $this->assertNotNull($activity);
        $this->assertSame($this->pumpType->id, $activity->equipment_type_id);
        $this->assertSame('maintenance', $activity->type);
        $this->assertSame('preventive', $activity->maintenance_classification);
        $this->assertSame('monthly', $activity->interval);
        $this->assertSame('qualitative', $activity->answer_type);

        $this->runImport(ActivityImporter::class, [
            [
                'equipment_type_id' => 'pump',
                'name' => 'Oil Change',
                'type' => 'maintenance',
                'maintenance_classification' => 'preventive',
                'interval' => 'weekly',
                'answer_type' => 'quantitative',
                'status' => 'nonaktif',
            ],
        ]);

        $this->assertSame(1, Activity::where('name', 'Oil Change')->count());

        $activity->refresh();

        $this->assertSame('weekly', $activity->interval);
        $this->assertSame('quantitative', $activity->answer_type);
        $this->assertFalse($activity->status);
    }

    public function test_importers_no_longer_use_mutate_before_create(): void
    {
        foreach ([AreaImporter::class, EquipmentImporter::class, EquipmentTypeImporter::class, ActivityImporter::class] as $importer) {
            $this->assertFalse(method_exists($importer, 'mutateBeforeCreate'));
        }
    }
}
