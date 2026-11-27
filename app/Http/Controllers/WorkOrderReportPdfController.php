<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Support\Report\ImageData;
use App\Support\UserVisibility;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkOrderReportPdfController extends Controller
{
    /**
     * Laporan hasil pekerjaan satu work order (PDF).
     *
     * Dipakai oleh panel Filament (web) maupun aplikasi Android (API).
     * Laporan hanya dapat dibuat setelah status work order Completed.
     */
    public function __invoke(Request $request, WorkOrder $workOrder): Response
    {
        abort_unless(
            UserVisibility::canAccessWorkOrder($workOrder, $request->user()),
            403,
            'Anda tidak berhak mengunduh laporan ini.',
        );

        abort_unless(
            $workOrder->status === WorkOrder::STATUSES['Completed'],
            422,
            'Laporan hanya dapat dibuat untuk work order yang sudah selesai.',
        );

        $workOrder->load([
            'equipment.equipmentType',
            'equipment.area',
            'issuedBy',
            'technicianCoordinator',
            'approvedBy',
            'maintenanceRequest',
            'workOrderActivities.activity',
            'workOrderWorkers.user',
            'findings',
        ]);

        $activities = $workOrder->workOrderActivities
            ->map(fn ($activity): array => [
                'activity' => $activity,
                'photos' => ImageData::thumbnailUris($activity->photos, 3, 140),
            ]);

        $logoPath = public_path('img/ppn.png');
        $logo = is_file($logoPath)
            ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath))
            : null;

        $pdf = Pdf::loadView('pdf.work-order', [
            'workOrder' => $workOrder,
            'activities' => $activities,
            'logo' => $logo,
        ])->setPaper('a4', 'portrait');

        $number = preg_replace('/[^0-9A-Za-z-]/', '-', $workOrder->work_order_number);

        return $pdf->download('laporan-wo-'.$number.'.pdf');
    }
}
