<?php

namespace App\Http\Controllers\Auth;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\HousekeepingDashboard;
use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Satu halaman login untuk semua panel.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return view('auth.login');
        }

        $home = $this->homeUrl($user);

        if ($home === null) {
            $this->logout($request);

            return redirect()->to('/login');
        }

        return redirect()->to($home);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();

        $home = $user->status ? $this->homeUrl($user) : null;

        if ($home === null) {
            $this->logout($request);

            throw ValidationException::withMessages([
                'email' => $user->status
                    ? 'Akun ini tidak memiliki akses ke panel mana pun.'
                    : 'Akun Anda tidak aktif.',
            ]);
        }

        return redirect()->to($home);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->logout($request);

        return redirect()->to('/login');
    }

    /**
     * Panel tujuan sesuai role user. Super admin diarahkan ke panel admin.
     */
    protected function homeUrl(User $user): ?string
    {
        $adminPanel = Filament::getPanel('admin');

        if ($user->canAccessPanel($adminPanel)) {
            // SPV GOS tidak punya akses ke Maintenance Dashboard (`/admin`),
            // jadi langsung arahkan ke Housekeeping Dashboard.
            if (! $user->hasAnyRole(Dashboard::ALLOWED_ROLES)) {
                return HousekeepingDashboard::getUrl(panel: 'admin');
            }

            return $adminPanel->getUrl();
        }

        if ($user->canAccessPanel(Filament::getPanel('app'))) {
            return Filament::getPanel('app')->getUrl();
        }

        return null;
    }

    protected function logout(Request $request): void
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
