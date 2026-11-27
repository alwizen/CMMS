<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNavigationGroupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_navigation_groups_are_defined_centrally_in_order(): void
    {
        $panel = Filament::getPanel('admin');

        $labels = array_map(
            fn ($group): string => $group->getLabel(),
            $panel->getNavigationGroups(),
        );

        $this->assertSame([
            'Performance',
            'Maintenance',
            'Housekeeping',
            'Master Data',
            'User & Permission',
        ], $labels);
    }

    public function test_every_admin_navigation_group_has_an_icon(): void
    {
        foreach (Filament::getPanel('admin')->getNavigationGroups() as $group) {
            $this->assertNotNull($group->getIcon(), "Group [{$group->getLabel()}] has no icon.");
        }
    }

    public function test_admin_sidebar_renders_configured_groups(): void
    {
        Gate::before(fn () => true);
        Role::findOrCreate('super_admin', 'web');

        $user = User::factory()->create(['status' => true]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Maintenance')
            ->assertSee('Housekeeping')
            ->assertSee('Master Data')
            ->assertSee('User &amp; Permission', false);
    }
}
