<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\HousekeepingDashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HousekeepingDashboardSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_dashboards_render(): void
    {
        Gate::before(fn () => true);
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));
        $this->actingAs($user);

        Livewire::test(Dashboard::class)->assertOk();
        Livewire::test(HousekeepingDashboard::class)->assertOk();
    }
}
