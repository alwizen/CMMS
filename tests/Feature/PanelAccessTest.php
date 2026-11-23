<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role, bool $status = true): User
    {
        $user = User::factory()->create(['status' => $status]);
        $user->assignRole(Role::findOrCreate($role, 'web'));

        return $user;
    }

    public function test_super_admin_can_access_admin_and_app_panels(): void
    {
        config(['app.env' => 'production']);

        $user = $this->makeUser('super_admin');

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('app')));
    }

    public function test_inactive_user_cannot_access_any_panel(): void
    {
        config(['app.env' => 'production']);

        $user = $this->makeUser('super_admin', status: false);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('app')));
    }

    public function test_supervisor_roles_only_access_admin_panel(): void
    {
        config(['app.env' => 'production']);

        foreach (['spv_mos', 'spv_gos', 'spv_hsse', 'pengawas'] as $role) {
            $user = $this->makeUser($role);

            $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')), $role);
            $this->assertFalse($user->canAccessPanel(Filament::getPanel('app')), $role);
        }
    }

    public function test_field_roles_only_access_app_panel(): void
    {
        config(['app.env' => 'production']);

        foreach (['mos', 'patra-jasa'] as $role) {
            $user = $this->makeUser($role);

            $this->assertTrue($user->canAccessPanel(Filament::getPanel('app')), $role);
            $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')), $role);
        }
    }

    public function test_production_login_page_redirects_authenticated_active_user_into_panel(): void
    {
        config(['app.env' => 'production']);

        $user = $this->makeUser('super_admin');

        $this->actingAs($user)
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_field_permissions_are_managed_by_shield(): void
    {
        Permission::findOrCreate('Create:WorkOrder', 'web');
        Permission::findOrCreate('Update:HousekeepingPlan', 'web');

        $mos = Role::findByName('mos', 'web');
        $patra = Role::findByName('patra-jasa', 'web');

        $this->assertTrue($mos->hasPermissionTo('ViewAny:WorkOrder'));
        $this->assertTrue($mos->hasPermissionTo('Create:MaintenanceRequest'));
        $this->assertFalse($mos->hasPermissionTo('Create:WorkOrder'));

        $this->assertTrue($patra->hasPermissionTo('ViewAny:HousekeepingPlan'));
        $this->assertTrue($patra->hasPermissionTo('Create:HousekeepingRequest'));
        $this->assertFalse($patra->hasPermissionTo('Update:HousekeepingPlan'));
    }

    public function test_app_panel_access_follows_shield_permission(): void
    {
        config(['app.env' => 'production']);

        $user = $this->makeUser('mos');

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('app')));

        Role::findByName('mos', 'web')->revokePermissionTo('View:FieldDashboard');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertFalse($user->fresh()->canAccessPanel(Filament::getPanel('app')));
    }

    public function test_shield_role_editor_loads_for_super_admin(): void
    {
        $admin = $this->makeUser('super_admin');
        $admin->givePermissionTo([
            Permission::findOrCreate('ViewAny:Role', 'web'),
            Permission::findOrCreate('View:Role', 'web'),
            Permission::findOrCreate('Update:Role', 'web'),
        ]);

        $role = Role::findByName('mos', 'web');

        $this->actingAs($admin)
            ->get("/admin/shield/roles/{$role->id}/edit")
            ->assertSuccessful();
    }

    public function test_field_user_is_redirected_from_admin_panel_to_app_panel(): void
    {
        $user = $this->makeUser('mos');

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect('/app');

        $this->actingAs($user)
            ->get('/app')
            ->assertSuccessful();
    }

    public function test_supervisor_is_redirected_from_app_panel_to_admin_panel(): void
    {
        $user = $this->makeUser('spv_mos');

        $this->actingAs($user)
            ->get('/app')
            ->assertRedirect('/admin');
    }
}
