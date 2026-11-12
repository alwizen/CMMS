<?php

namespace Tests\Feature;

use App\Filament\Resources\HousekeepingAreas\Pages\CreateHousekeepingArea;
use App\Filament\Resources\HousekeepingPlans\Pages\CreateHousekeepingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HousekeepingSupervisorTest extends TestCase
{
    use RefreshDatabase;

    public function test_area_has_supervisor_field_and_plan_does_not(): void
    {
        Gate::before(fn () => true);
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateHousekeepingArea::class)
            ->assertFormFieldExists('supervisor_id');

        Livewire::test(CreateHousekeepingPlan::class)
            ->assertFormFieldDoesNotExist('supervisor_id');
    }
}
