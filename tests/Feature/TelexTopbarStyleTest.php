<?php

namespace Tests\Feature;

use App\Models\User;
use Elemind\PressFilamentTheme\PressFilamentTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TelexTopbarStyleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        Role::findOrCreate('super_admin', 'web');

        $this->user = User::factory()->create(['status' => true]);
        $this->user->assignRole('super_admin');
    }

    public function test_telex_gets_light_topbar_override(): void
    {
        $this->actingAs($this->user)
            ->withCookie(PressFilamentTheme::COOKIE, 'telex')
            ->get('/admin/my-profile')
            ->assertOk()
            ->assertSee('data-press-telex-topbar', false);
    }

    public function test_other_variants_do_not_get_the_override(): void
    {
        $this->actingAs($this->user)
            ->withCookie(PressFilamentTheme::COOKIE, 'broadsheet')
            ->get('/admin/my-profile')
            ->assertOk()
            ->assertDontSee('data-press-telex-topbar', false);
    }

    public function test_override_is_rendered_after_the_preset_stylesheet(): void
    {
        $html = $this->actingAs($this->user)
            ->withCookie(PressFilamentTheme::COOKIE, 'telex')
            ->get('/admin/my-profile')
            ->getContent();

        $presetPosition = strpos($html, 'press-preset-telex');
        $overridePosition = strpos($html, 'data-press-telex-topbar');

        $this->assertNotFalse($presetPosition);
        $this->assertNotFalse($overridePosition);
        $this->assertGreaterThan($presetPosition, $overridePosition);
    }
}
