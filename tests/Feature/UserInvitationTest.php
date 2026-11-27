<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use App\Support\UserInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserInvitationTest extends TestCase
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

    public function test_super_admin_can_invite_user_with_role_and_fungsi(): void
    {
        Notification::fake();
        $this->actingAs($this->superAdmin);

        Livewire::test(ManageUsers::class)
            ->assertActionVisible('inviteUser')
            ->callAction('inviteUser', data: [
                'email' => 'Petugas.Baru@Example.com',
                'role' => 'mos',
                'fungsi' => 'MOS LTSA',
            ])
            ->assertHasNoActionErrors();

        $user = User::where('email', 'petugas.baru@example.com')->firstOrFail();

        $this->assertFalse($user->status);
        $this->assertSame('MOS LTSA', $user->fungsi);
        $this->assertTrue($user->hasRole('mos'));
        $this->assertTrue($user->hasPendingInvitation());
        $this->assertNotNull($user->invited_at);

        Notification::assertSentTo($user, UserInvitationNotification::class);
    }

    public function test_fungsi_is_optional_when_inviting(): void
    {
        Notification::fake();
        $this->actingAs($this->superAdmin);

        Livewire::test(ManageUsers::class)
            ->callAction('inviteUser', data: [
                'email' => 'tanpa.fungsi@example.com',
                'role' => 'pengawas',
            ])
            ->assertHasNoActionErrors();

        $user = User::where('email', 'tanpa.fungsi@example.com')->firstOrFail();

        $this->assertNull($user->fungsi);
        $this->assertTrue($user->hasRole('pengawas'));
    }

    public function test_invite_action_is_hidden_for_non_super_admin(): void
    {
        $pengawas = User::factory()->create(['status' => true]);
        $pengawas->assignRole('pengawas');

        $this->actingAs($pengawas);

        Livewire::test(ManageUsers::class)
            ->assertActionHidden('inviteUser');
    }

    public function test_cannot_invite_an_email_that_is_already_active(): void
    {
        Notification::fake();
        $this->actingAs($this->superAdmin);

        $active = User::factory()->create([
            'email' => 'aktif@example.com',
            'status' => true,
            'invitation_token' => null,
        ]);
        $active->assignRole('mos');

        Livewire::test(ManageUsers::class)
            ->callAction('inviteUser', data: [
                'email' => 'aktif@example.com',
                'role' => 'mos',
            ])
            ->assertHasActionErrors(['email']);

        Notification::assertNothingSent();
    }

    public function test_resending_invitation_rotates_token_for_pending_user(): void
    {
        Notification::fake();
        $this->actingAs($this->superAdmin);

        $pending = User::create([
            'name' => 'Pending',
            'email' => 'pending@example.com',
            'password' => 'rahasia-lama',
            'status' => false,
            'invitation_token' => 'token-lama',
            'invited_at' => now()->subDay(),
        ]);
        $pending->assignRole('mos');

        Livewire::test(ManageUsers::class)
            ->callAction('inviteUser', data: [
                'email' => 'pending@example.com',
                'role' => 'mos',
                'fungsi' => 'HSSE',
            ])
            ->assertHasNoActionErrors();

        $pending->refresh();

        $this->assertNotSame('token-lama', $pending->invitation_token);
        $this->assertTrue($pending->hasPendingInvitation());
        $this->assertSame('HSSE', $pending->fungsi);

        Notification::assertSentTo($pending, UserInvitationNotification::class);
    }

    public function test_invitation_link_activates_account_and_sets_password(): void
    {
        $user = User::create([
            'name' => 'Diundang',
            'email' => 'diundang@example.com',
            'password' => 'rahasia-acak',
            'status' => false,
        ]);
        $user->assignRole('mos');

        UserInvitation::issue($user);

        $url = UserInvitation::acceptUrl($user, $user->fresh()->invitation_token);

        $this->get($url)->assertOk();

        $this->post($url, [
            'name' => 'Nama Baru',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertRedirect(route('login'));

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertTrue($user->status);
        $this->assertTrue(Hash::check('password-baru-123', $user->password));
        $this->assertNull($user->invitation_token);
        $this->assertNotNull($user->invitation_accepted_at);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('mos'));
    }

    public function test_invitation_link_cannot_be_used_twice(): void
    {
        $user = User::create([
            'name' => 'Diundang',
            'email' => 'sekali@example.com',
            'password' => 'rahasia-acak',
            'status' => false,
        ]);

        UserInvitation::issue($user);

        $url = UserInvitation::acceptUrl($user, $user->fresh()->invitation_token);

        $this->post($url, [
            'name' => 'Nama Baru',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertRedirect(route('login'));

        $this->get($url)->assertForbidden();
    }

    public function test_invitation_with_wrong_token_is_rejected(): void
    {
        $user = User::create([
            'name' => 'Diundang',
            'email' => 'salah@example.com',
            'password' => 'rahasia-acak',
            'status' => false,
        ]);

        UserInvitation::issue($user);

        $url = URL::temporarySignedRoute('invitations.accept', now()->addDay(), [
            'user' => $user->id,
            'token' => 'token-palsu',
        ]);

        $this->get($url)->assertForbidden();
    }

    public function test_invitation_without_valid_signature_is_rejected(): void
    {
        $user = User::create([
            'name' => 'Diundang',
            'email' => 'unsigned@example.com',
            'password' => 'rahasia-acak',
            'status' => false,
        ]);

        UserInvitation::issue($user);

        $this->get(route('invitations.accept', [
            'user' => $user->id,
            'token' => $user->fresh()->invitation_token,
        ]))->assertForbidden();
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = User::create([
            'name' => 'Diundang',
            'email' => 'konfirmasi@example.com',
            'password' => 'rahasia-acak',
            'status' => false,
        ]);

        UserInvitation::issue($user);

        $url = UserInvitation::acceptUrl($user, $user->fresh()->invitation_token);

        $this->post($url, [
            'name' => 'Nama Baru',
            'password' => 'password-baru-123',
            'password_confirmation' => 'beda',
        ])->assertSessionHasErrors('password');

        $this->assertFalse($user->fresh()->status);
    }
}
