<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BreezyProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        Role::findOrCreate('super_admin', 'web');
    }

    public function test_admin_my_profile_page_renders_with_components(): void
    {
        $user = User::factory()->create(['status' => true]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $this->get('/admin/my-profile')
            ->assertOk()
            ->assertSee('Profil saya')
            ->assertSee('Kata Sandi');
    }

    public function test_app_my_profile_page_renders(): void
    {
        $user = User::factory()->create(['status' => true]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $this->get('/app/my-profile')
            ->assertOk()
            ->assertSee('Profil saya');
    }

    public function test_two_factor_route_is_registered_for_admin_panel(): void
    {
        $this->assertTrue(
            Filament::getPanel('admin')->hasPlugin('filament-breezy'),
        );
    }
}
