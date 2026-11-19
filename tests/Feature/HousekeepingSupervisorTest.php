<?php

namespace Tests\Feature;

use App\Filament\Resources\HousekeepingAreas\Pages\CreateHousekeepingArea;
use App\Filament\Resources\HousekeepingPlans\Pages\CreateHousekeepingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HousekeepingSupervisorTest extends TestCase
{
    use RefreshDatabase;

    public function test_area_has_supervisor_field_and_plan_does_not(): void
    {
        Gate::before(fn () => true);

        Role::findOrCreate('spv_gos', 'web');

        $spv = User::factory()->create(['status' => true]);
        $spv->assignRole('spv_gos');
        $this->actingAs($spv);

        Livewire::test(CreateHousekeepingArea::class)
            ->assertFormFieldExists('supervisor_id');

        Livewire::test(CreateHousekeepingPlan::class)
            ->assertFormFieldDoesNotExist('supervisor_id');
    }
}
