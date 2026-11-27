<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class InvitationController extends Controller
{
    /**
     * Tampilkan form aktivasi akun dari tautan undangan.
     */
    public function show(Request $request, User $user): View
    {
        $this->ensureValidInvitation($request, $user);

        return view('auth.accept-invitation', [
            'user' => $user,
            'action' => $request->fullUrl(),
        ]);
    }

    /**
     * Simpan password dan aktifkan akun.
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        $this->ensureValidInvitation($request, $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $user->forceFill([
            'name' => $data['name'],
            'password' => Hash::make($data['password']),
            'status' => true,
            'email_verified_at' => now(),
            'invitation_accepted_at' => now(),
            'invitation_token' => null,
        ])->save();

        return redirect()
            ->route('login')
            ->with('status', 'Akun berhasil diaktifkan. Silakan masuk dengan password baru Anda.');
    }

    protected function ensureValidInvitation(Request $request, User $user): void
    {
        $token = (string) $request->query('token');

        abort_unless(
            filled($token) && filled($user->invitation_token) && hash_equals($user->invitation_token, $token),
            403,
            'Tautan undangan tidak valid atau sudah digunakan.',
        );

        abort_if(
            $user->invitation_accepted_at !== null,
            403,
            'Undangan ini sudah digunakan.',
        );
    }
}
