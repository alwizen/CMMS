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
     * SPV GOS membuat plan lalu patra-jasa mengerjakan. Approval SPV GOS
     * adalah trigger laporan: semua plan yang masuk laporan untuk tanggal itu
     * harus sudah direview/disetujui. Selama masih ada plan yang belum
     * di-approve (atau ditolak), laporan ditahan.
     *
     * @return array{allowed: bool, pending: array<int, HousekeepingPlan>, rejected: array<int, HousekeepingPlan>}
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

        $pending = $plans
            ->filter(fn (HousekeepingPlan $plan): bool => $plan->approval_status === HousekeepingPlan::APPROVAL_PENDING)
            ->values();

        $rejected = $plans
            ->filter(fn (HousekeepingPlan $plan): bool => $plan->approval_status === HousekeepingPlan::APPROVAL_REJECTED)
            ->values();

        return [
            'allowed' => $pending->isEmpty() && $rejected->isEmpty(),
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

        return 'Laporan belum bisa digenerate: plan '.implode(' dan plan ', $parts).'.';
    }

    public static function allows(CarbonInterface|string $date): bool
    {
        return static::inspect($date)['allowed'];
    }
}
