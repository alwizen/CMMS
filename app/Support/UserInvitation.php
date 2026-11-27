<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Alur undangan pengguna (invite-only, tanpa pendaftaran mandiri).
 */
class UserInvitation
{
    /**
     * Buat/putar token undangan lalu kirim email ke pengguna.
     */
    public static function issue(User $user): void
    {
        $token = Str::random(64);

        $user->forceFill([
            'invitation_token' => $token,
            'invited_at' => now(),
            'invitation_accepted_at' => null,
        ])->save();

        $user->notify(new UserInvitationNotification(
            $user,
            static::acceptUrl($user, $token),
        ));
    }

    /**
     * Tautan bertanda tangan berumur pendek menuju halaman aktivasi akun.
     */
    public static function acceptUrl(User $user, string $token): string
    {
        return URL::temporarySignedRoute(
            'invitations.accept',
            now()->addDays((int) config('auth.invitation_expiry_days', 7)),
            ['user' => $user->getKey(), 'token' => $token],
        );
    }
}
