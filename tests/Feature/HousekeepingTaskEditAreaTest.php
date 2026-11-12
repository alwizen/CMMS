<?php

namespace Tests\Feature;

use App\Filament\Resources\HousekeepingTasks\Pages\EditHousekeepingTask;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HousekeepingTaskEditAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_hydrates_area_from_facility(): void
    {
        Gate::before(fn () => true);
        $this->actingAs(User::factory()->create());

        $company = Company::create(['code' => 'HK', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'Area A']);
        $facility = HousekeepingFacility::create(['housekeeping_area_id' => $area->id, 'name' => 'Oil Catcher', 'is_active' => true]);
        $task = HousekeepingTask::create(['housekeeping_facility_id' => $facility->id, 'name' => 'Cek', 'is_active' => true]);

        Livewire::test(EditHousekeepingTask::class, ['record' => $task->getKey()])
            ->assertOk()
            ->assertFormSet(['area_id' => $area->id, 'housekeeping_facility_id' => $facility->id]);
    }
}
