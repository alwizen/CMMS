<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserCreateTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        foreach (['super_admin', 'pengawas', 'mos'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->superAdmin = User::factory()->create(['status' => true]);
        $this->superAdmin->assignRole('super_admin');
    }

    public function test_super_admin_can_create_an_active_user(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ManageUsers::class)
            ->assertActionVisible('createUser')
            ->callAction('createUser', data: [
                'name' => 'Petugas Baru',
                'email' => 'Petugas.Baru@Example.com',
                'password' => 'SI_SECRET_PASSWORD_MR7X2K9P',
                'role' => 'mos',
                'fungsi' => 'MOS TAD',
                'status' => true,
            ])
            ->assertHasNoActionErrors();

        $user = User::where('email', 'petugas.baru@example.com')->firstOrFail();

        $this->assertSame('Petugas Baru', $user->name);
        $this->assertTrue($user->status);
        $this->assertSame('MOS TAD', $user->fungsi);
        $this->assertTrue($user->hasRole('mos'));
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('SI_SECRET_PASSWORD_MR7X2K9P', $user->password));
    }

    public function test_create_user_requires_unique_email(): void
    {
        $this->actingAs($this->superAdmin);

        User::factory()->create(['email' => 'sudah-ada@example.com']);

        Livewire::test(ManageUsers::class)
            ->callAction('createUser', data: [
                'name' => 'Duplikat',
                'email' => 'sudah-ada@example.com',
                'password' => 'SI_SECRET_PASSWORD_MR7X2K9P',
                'role' => 'mos',
                'status' => true,
            ])
            ->assertHasActionErrors(['email']);
    }

    public function test_create_user_requires_a_password(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ManageUsers::class)
            ->callAction('createUser', data: [
                'name' => 'Tanpa Password',
                'email' => 'tanpa-password@example.com',
                'password' => '',
                'role' => 'mos',
            ])
            ->assertHasActionErrors(['password']);
    }

    public function test_create_action_is_hidden_for_non_super_admin(): void
    {
        $pengawas = User::factory()->create(['status' => true]);
        $pengawas->assignRole('pengawas');

        $this->actingAs($pengawas);

        Livewire::test(ManageUsers::class)
            ->assertActionHidden('createUser');
    }
}
