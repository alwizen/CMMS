<?php

namespace App\Http\Middleware;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Middleware autentikasi panel Filament.
 *
 * Bila user sudah login tetapi tidak berhak membuka panel yang diminta
 * (mis. petugas `mos` membuka `/admin`), user diarahkan ke panel yang
 * memang boleh ia akses (mis. `/app`) alih-alih menerima halaman 403.
 */
class AuthenticatePanel extends FilamentAuthenticate
{
    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);

            return;
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        /** @var Authenticatable&FilamentUser $user */
        $user = $guard->user();

        $panel = Filament::getCurrentOrDefaultPanel();

        if (! $user instanceof FilamentUser) {
            // Perilaku bawaan: hanya izinkan di lingkungan local.
            abort_if(config('app.env') !== 'local', 403);

            return;
        }

        if ($user->canAccessPanel($panel)) {
            return;
        }

        $fallback = collect(Filament::getPanels())
            ->first(fn ($candidate): bool => $candidate->getId() !== $panel->getId()
                && $user->canAccessPanel($candidate));

        if ($fallback !== null) {
            throw new HttpResponseException(redirect()->to($fallback->getUrl()));
        }

        // Tidak ada panel yang bisa diakses: akhiri sesi agar tidak terjebak
        // pada halaman 403, lalu kembalikan ke halaman login.
        $guard->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        throw new HttpResponseException(redirect()->to('/login'));
    }
}
