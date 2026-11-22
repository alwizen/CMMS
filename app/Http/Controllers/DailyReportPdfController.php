<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\HousekeepingReportGate;
use App\Support\Report\DailyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DailyReportPdfController extends Controller
{
    /**
     * Generate Laporan.
     *
     * Query:
     * - date  : tanggal laporan (default hari ini)
     * - scope : maintenance | housekeeping | all (default all)
     */
    public function __invoke(Request $request)
    {
        abort_unless(
            $request->user()?->hasAnyRole(User::ADMIN_PANEL_ROLES),
            403,
            'Anda tidak berhak mengunduh laporan ini.',
        );

        $date = $request->query('date') ?: now()->toDateString();
        $scope = $request->query('scope') ?: 'all';

        if (! in_array($scope, ['maintenance', 'housekeeping', 'all'], true)) {
            $scope = 'all';
        }

        if ($scope !== 'maintenance') {
            $inspection = HousekeepingReportGate::inspect($date);

            abort_unless(
                $inspection['allowed'],
                422,
                HousekeepingReportGate::message($inspection),
            );
        }

        $report = app(DailyReport::class)->build($date);

        $pdf = Pdf::loadView('pdf.daily-report', [
            'report' => $report,
            'scope' => $scope,
        ])->setPaper('a4', 'portrait');

        $prefix = match ($scope) {
            'maintenance' => 'laporan-harian-maintenance-',
            'housekeeping' => 'laporan-harian-housekeeping-',
            default => 'laporan-harian-',
        };

        return $pdf->download($prefix.date('Ymd', strtotime($date)).'.pdf');
    }
}
