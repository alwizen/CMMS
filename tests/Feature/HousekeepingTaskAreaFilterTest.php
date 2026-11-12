<?php

namespace Tests\Feature;

use App\Filament\Resources\HousekeepingTasks\Pages\CreateHousekeepingTask;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HousekeepingTaskAreaFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_area_field_filters_facilities_and_saves_task(): void
    {
        Gate::before(fn () => true);
        $this->actingAs(User::factory()->create());

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);
        $areaA = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'Area A']);
        $areaB = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'b', 'name' => 'Area B']);
        $facilityA = HousekeepingFacility::create(['housekeeping_area_id' => $areaA->id, 'name' => 'Oil Catcher', 'is_active' => true]);
        $facilityB = HousekeepingFacility::create(['housekeeping_area_id' => $areaB->id, 'name' => 'Selokan B', 'is_active' => true]);

        $component = Livewire::test(CreateHousekeepingTask::class)
            ->assertFormFieldExists('area_id')
            ->fillForm(['area_id' => $areaA->id]);

        // Facility select should only contain facilities of Area A.
        $options = $component->instance()->form->getComponent('housekeeping_facility_id')->getOptions();
        $this->assertArrayHasKey($facilityA->id, $options);
        $this->assertArrayNotHasKey($facilityB->id, $options);

        $component
            ->fillForm([
                'housekeeping_facility_id' => $facilityA->id,
                'name' => 'Cek oil catcher',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $task = HousekeepingTask::where('name', 'Cek oil catcher')->first();
        $this->assertNotNull($task);
        $this->assertSame($facilityA->id, $task->housekeeping_facility_id);
    }
}
