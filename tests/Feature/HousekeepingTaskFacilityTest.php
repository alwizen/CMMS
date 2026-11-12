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

class HousekeepingTaskFacilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_task_belongs_to_facility(): void
    {
        Gate::before(fn () => true);
        $this->actingAs(User::factory()->create());

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'Area A']);
        $facility = HousekeepingFacility::create([
            'housekeeping_area_id' => $area->id,
            'name' => 'Oil Catcher',
            'is_active' => true,
        ]);

        Livewire::test(CreateHousekeepingTask::class)
            ->fillForm([
                'area_id' => $area->id,
                'housekeeping_facility_id' => $facility->id,
                'name' => 'Cek oil catcher',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $task = HousekeepingTask::where('name', 'Cek oil catcher')->first();
        $this->assertNotNull($task);
        $this->assertSame($facility->id, $task->housekeeping_facility_id);
        $this->assertSame($area->id, $task->facility->housekeeping_area_id);
    }
}
