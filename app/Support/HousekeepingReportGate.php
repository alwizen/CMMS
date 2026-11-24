<?php

namespace App\Support;

use App\Models\HousekeepingPlan;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class HousekeepingReportGate
{
    /**
     * Apakah laporan harian housekeeping untuk tanggal tertentu boleh digenerate.
     *
     * Laporan hanya memuat plan yang sudah direview/disetujui SPV GOS. Plan
     * yang masih menunggu atau ditolak tidak menahan laporan: plan tersebut
     * dilewati dan dilaporkan lewat `pending`/`rejected` agar bisa ditindak
     * lanjuti. Laporan baru ditahan bila sama sekali belum ada plan yang
     * disetujui pada tanggal tersebut.
     *
     * @return array{allowed: bool, approved: array<int, HousekeepingPlan>, pending: array<int, HousekeepingPlan>, rejected: array<int, HousekeepingPlan>}
     */
    public static function inspect(CarbonInterface|string $date): array
    {
        $day = Carbon::parse($date)->toDateString();

        // Plan yang muncul pada laporan tanggal ini: plan yang dimulai pada
        // tanggal tersebut, atau plan lain yang masih berjalan.
        $plans = HousekeepingPlan::query()
            ->whereDate('plan_date', '<=', $day)
            ->where(function ($query) use ($day): void {
                $query->whereDate('plan_date', $day)
                    ->orWhere('status', '!=', HousekeepingPlan::STATUSES['Completed']);
            })
            ->with('area:id,name')
            ->orderBy('plan_number')
            ->get();

        $approved = $plans
            ->filter(fn (HousekeepingPlan $plan): bool => $plan->approval_status === HousekeepingPlan::APPROVAL_APPROVED)
            ->values();

        $pending = $plans
            ->filter(fn (HousekeepingPlan $plan): bool => $plan->approval_status === HousekeepingPlan::APPROVAL_PENDING)
            ->values();

        $rejected = $plans
            ->filter(fn (HousekeepingPlan $plan): bool => $plan->approval_status === HousekeepingPlan::APPROVAL_REJECTED)
            ->values();

        return [
            // Tidak ada plan sama sekali -> laporan kosong tetap boleh dibuat.
            // Ada plan tetapi belum satu pun disetujui -> tidak ada isi laporan.
            'allowed' => $approved->isNotEmpty() || $plans->isEmpty(),
            'approved' => $approved->all(),
            'pending' => $pending->all(),
            'rejected' => $rejected->all(),
        ];
    }

    /**
     * Pesan penolakan untuk UI/API.
     */
    public static function message(array $inspection): string
    {
        $parts = [];

        if (! empty($inspection['pending'])) {
            $numbers = implode(', ', array_map(
                fn (HousekeepingPlan $plan): string => (string) $plan->plan_number,
                $inspection['pending'],
            ));

            $parts[] = 'belum direview/disetujui SPV GOS ('.$numbers.')';
        }

        if (! empty($inspection['rejected'])) {
            $numbers = implode(', ', array_map(
                fn (HousekeepingPlan $plan): string => (string) $plan->plan_number,
                $inspection['rejected'],
            ));

            $parts[] = 'ditolak SPV GOS ('.$numbers.')';
        }

        if ($parts === []) {
            return 'Laporan belum bisa digenerate: belum ada plan housekeeping pada tanggal ini.';
        }

        return 'Laporan belum bisa digenerate: belum ada plan yang disetujui SPV GOS. Plan '.implode(' dan plan ', $parts).'.';
    }

    public static function allows(CarbonInterface|string $date): bool
    {
        return static::inspect($date)['allowed'];
    }
}
