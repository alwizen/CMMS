<?php

namespace Tests\Feature;

use App\Filament\Resources\HousekeepingManpowers\Pages\CreateHousekeepingManpower;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingManpower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HousekeepingManpowerAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manpower_is_created_with_area(): void
    {
        Gate::before(fn () => true);
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::create(['code' => 'HK-1', 'name' => 'HK Co']);
        $area = HousekeepingArea::create(['company_id' => $company->id, 'code' => 'a', 'name' => 'Area A']);

        Livewire::test(CreateHousekeepingManpower::class)
            ->fillForm([
                'housekeeping_area_id' => $area->id,
                'name' => 'Budi',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('housekeeping_manpowers', [
            'name' => 'Budi',
            'housekeeping_area_id' => $area->id,
        ]);
    }
}
