<?php

namespace Tests\Feature;

use App\Filament\Resources\MeterLogs\Pages\CreateMeterLog;
use App\Filament\Resources\MeterLogs\Pages\EditMeterLog;
use App\Models\Area;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\MeterLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class MeterLogFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected EquipmentType $pumpType;

    protected EquipmentType $tankType;

    protected Equipment $pump;

    protected Equipment $tank;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.env' => 'local']);
        Gate::before(fn (): bool => true);

        $this->user = User::factory()->create();

        $company = Company::create(['code' => 'CO', 'name' => 'Demo Company']);
        $area = Area::create(['company_id' => $company->id, 'code' => 'LOAD', 'name' => 'Loading']);
        $this->pumpType = EquipmentType::create(['code' => 'pump', 'name' => 'Pompa']);
        $this->tankType = EquipmentType::create(['code' => 'tank', 'name' => 'Tangki']);

        $this->pump = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $this->pumpType->id,
            'tag_number' => 'P-101',
            'status' => 'active',
        ]);

        $this->tank = Equipment::create([
            'area_id' => $area->id,
            'equipment_type_id' => $this->tankType->id,
            'tag_number' => 'T-201',
            'status' => 'active',
        ]);
    }

    public function test_equipment_options_are_filtered_by_selected_type(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateMeterLog::class)
            ->assertFormFieldExists('equipment_type_id')
            ->fillForm(['equipment_type_id' => $this->pumpType->id])
            ->assertFormFieldExists('equipment_id')
            ->assertSee('P-101')
            ->assertDontSee('T-201')
            ->fillForm(['equipment_type_id' => $this->tankType->id])
            ->assertSee('T-201')
            ->assertDontSee('P-101');
    }

    public function test_equipment_field_is_disabled_until_type_is_selected(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateMeterLog::class)
            ->assertFormFieldIsDisabled('equipment_id')
            ->fillForm(['equipment_type_id' => $this->pumpType->id])
            ->assertFormFieldIsEnabled('equipment_id');
    }

    public function test_edit_form_prefills_equipment_type_from_record(): void
    {
        $meterLog = MeterLog::create([
            'equipment_id' => $this->tank->id,
            'reading_date' => now()->toDateString(),
            'value' => 1500,
            'recorded_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        Livewire::test(EditMeterLog::class, ['record' => $meterLog->getRouteKey()])
            ->assertFormSet(['equipment_type_id' => $this->tankType->id])
            ->assertSee('T-201');
    }

    public function test_meter_log_can_be_created_with_selected_equipment(): void
    {
        $this->actingAs($this->user);

        Livewire::test(CreateMeterLog::class)
            ->fillForm([
                'equipment_type_id' => $this->pumpType->id,
                'equipment_id' => $this->pump->id,
                'reading_date' => now()->toDateString(),
                'value' => 123.45,
                'recorded_by' => $this->user->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('meter_logs', [
            'equipment_id' => $this->pump->id,
            'value' => 123.45,
        ]);
    }
}
