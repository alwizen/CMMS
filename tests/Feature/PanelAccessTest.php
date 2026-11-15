<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_access_panel_even_in_production(): void
    {
        config(['app.env' => 'production']);

        $user = User::factory()->create(['status' => true]);

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_inactive_user_cannot_access_panel(): void
    {
        config(['app.env' => 'production']);

        $user = User::factory()->create(['status' => false]);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_production_login_page_redirects_authenticated_active_user_into_panel(): void
    {
        config(['app.env' => 'production']);

        $user = User::factory()->create(['status' => true]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertSuccessful();
    }
}
