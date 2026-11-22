<?php

namespace App\Http\Controllers;

use App\Support\HousekeepingReportGate;
use App\Support\Report\DailyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class FieldDailyReportPdfController extends Controller
{
    /**
     * Laporan harian housekeeping untuk panel lapangan (`/app`).
     */
    public function __invoke(Request $request)
    {
        abort_unless(
            $request->user()?->can('View:HousekeepingReport'),
            403,
            'Anda tidak berhak mengunduh laporan ini.',
        );

        $date = $request->query('date') ?: now()->toDateString();

        $inspection = HousekeepingReportGate::inspect($date);

        abort_unless(
            $inspection['allowed'],
            422,
            HousekeepingReportGate::message($inspection),
        );

        $report = app(DailyReport::class)->build($date);

        $pdf = Pdf::loadView('pdf.daily-report', [
            'report' => $report,
            'scope' => 'housekeeping',
        ])->setPaper('a4', 'portrait');

        return $pdf->download('laporan-harian-housekeeping-'.date('Ymd', strtotime($date)).'.pdf');
    }
}
