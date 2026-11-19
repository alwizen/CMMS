<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\HousekeepingReportGate;
use App\Support\Report\DailyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DailyReportController extends Controller
{
    /**
     * Generate laporan harian housekeeping untuk aplikasi mobile.
     *
     * Query:
     * - date  : tanggal laporan (default hari ini)
     * - scope : housekeeping (default) | all
     *
     * Hanya user housekeeping (patra-jasa) atau role akses penuh yang boleh
     * mengunduh laporan ini.
     */
    public function housekeeping(Request $request): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless($this->canAccess($user), 403, 'Anda tidak berhak mengunduh laporan ini.');

        $date = $request->query('date') ?: now()->toDateString();

        $inspection = HousekeepingReportGate::inspect($date);

        if (! $inspection['allowed']) {
            return response()->json([
                'success' => false,
                'message' => HousekeepingReportGate::message($inspection),
                'data' => [
                    'pending_plans' => array_map(
                        fn ($plan): string => (string) $plan->plan_number,
                        $inspection['pending'],
                    ),
                    'rejected_plans' => array_map(
                        fn ($plan): string => (string) $plan->plan_number,
                        $inspection['rejected'],
                    ),
                ],
            ], 422);
        }

        $scope = 'housekeeping';

        $report = app(DailyReport::class)->build($date);

        $pdf = Pdf::loadView('pdf.daily-report', [
            'report' => $report,
            'scope' => $scope,
        ])->setPaper('a4', 'portrait');

        $filename = 'laporan-harian-housekeeping-'.date('Ymd', strtotime($date)).'.pdf';

        $contents = $pdf->output();

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($contents),
        ]);
    }

    protected function canAccess(?User $user): bool
    {
        if (! $user || ! $user->status) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'spv_gos', 'pengawas', 'patra-jasa']);
    }
}
