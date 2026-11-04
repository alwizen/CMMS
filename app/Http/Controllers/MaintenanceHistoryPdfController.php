<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MaintenanceHistoryPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $from = $request->query('from');
        $until = $request->query('until');

        $query = MaintenanceHistory::with(['equipment.equipmentType', 'performedBy'])
            ->orderByDesc('maintenance_date');

        if ($from) {
            $query->whereDate('maintenance_date', '>=', $from);
        }

        if ($until) {
            $query->whereDate('maintenance_date', '<=', $until);
        }

        $query->whereHas('equipment', function ($q) use ($request) {
            if ($request->filled('equipment_id')) {
                $q->where('id', $request->equipment_id);
            }

            if ($request->filled('area_id')) {
                $q->where('area_id', $request->area_id);
            }
        });

        $histories = $query->limit(500)->get();

        $pdf = Pdf::loadView('pdf.maintenance-history', [
            'histories' => $histories,
            'from' => $from,
            'until' => $until,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('maintenance-history-'.now()->format('Ymd_His').'.pdf');
    }
}
