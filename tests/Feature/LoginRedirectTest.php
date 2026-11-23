<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role, bool $status = true): User
    {
        $user = User::factory()->create(['status' => $status]);
        $user->assignRole(Role::findOrCreate($role, 'web'));

        return $user;
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Selamat datang kembali')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    public function test_panel_login_routes_redirect_to_shared_login(): void
    {
        $this->get('/admin/login')->assertRedirect('/login');
        $this->get('/app/login')->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_panel_to_shared_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertRedirect('/login');
    }

    public function test_field_user_is_redirected_to_app_panel(): void
    {
        foreach (['mos', 'patra-jasa'] as $role) {
            $user = $this->makeUser($role);

            $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect('/app');

            $this->assertAuthenticatedAs($user);

            $this->post('/logout');
        }
    }

    public function test_supervisor_and_admin_are_redirected_to_admin_panel(): void
    {
        foreach (['super_admin', 'spv_mos', 'spv_hsse', 'pengawas'] as $role) {
            $user = $this->makeUser($role);

            $this->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])->assertRedirect('/admin');

            $this->assertAuthenticatedAs($user);

            $this->post('/logout');
        }
    }

    public function test_spv_gos_is_redirected_to_housekeeping_dashboard(): void
    {
        $user = $this->makeUser('spv_gos');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/admin/housekeeping-dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = $this->makeUser('mos');

        $this->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'bukan-password-yang-benar',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->makeUser('mos', status: false);

        $this->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_visiting_login_is_redirected_home(): void
    {
        $field = $this->makeUser('mos');
        $admin = $this->makeUser('spv_mos');

        $this->actingAs($field)->get('/login')->assertRedirect('/app');
        $this->actingAs($admin)->get('/login')->assertRedirect('/admin');
    }

    public function test_logout_returns_to_shared_login(): void
    {
        $user = $this->makeUser('mos');

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_filament_logout_returns_to_shared_login(): void
    {
        $user = $this->makeUser('super_admin');

        $this->actingAs($user)
            ->post('/admin/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
