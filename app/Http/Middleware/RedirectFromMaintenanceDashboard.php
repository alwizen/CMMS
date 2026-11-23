<?php

namespace App\Http\Middleware;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\HousekeepingDashboard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maintenance Dashboard (`/admin`) hanya untuk role tertentu. Bila user sudah
 * login tetapi tidak berhak (mis. `spv_gos`), arahkan ke Housekeeping Dashboard
 * alih-alih menampilkan halaman 403.
 */
class RedirectFromMaintenanceDashboard
{
    /**
     * Role yang diarahkan ke Housekeeping Dashboard, bukan ditolak 403.
     *
     * @var list<string>
     */
    private const REDIRECTED_ROLES = [
        'spv_gos',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || Dashboard::canAccess()) {
            return $next($request);
        }

        if ($user->hasAnyRole(self::REDIRECTED_ROLES)) {
            return redirect()->to(HousekeepingDashboard::getUrl(panel: 'admin'));
        }

        // Tidak punya halaman tujuan lain: biarkan halaman menolak dengan 403.
        return $next($request);
    }
}
