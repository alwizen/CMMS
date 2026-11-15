<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\Equipment;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WorkOrderGenerator
{
    public const DAILY_INTERVAL = 'daily';

    /**
     * Buat Work Order langsung untuk equipment terpilih (tanpa Plan/Schedule).
     *
     * Syarat per equipment:
     * - Wajib punya penanggung jawab (responsible_user_id).
     * - Tipe equipment harus punya activity aktif dengan interval yang dipilih.
     * Equipment yang tidak memenuhi syarat dilewati.
     *
     * @param  array<int, int>  $equipmentIds
     * @return array{created: int, skipped: array<int, string>, skip_reasons: array<string, string>}
     */
    public function generateDirect(
        array $equipmentIds,
        string $interval,
        int $quantity,
        string $startTime,
        bool $skipWeekend,
        int $issuedBy,
    ): array {
        $interval = strtolower(trim($interval));
        $quantity = max(1, $quantity);

        $equipments = Equipment::query()
            ->with('equipmentType')
            ->whereIn('id', $equipmentIds)
            ->get();

        $created = 0;
        $skipped = [];
        $skipReasons = [];

        foreach ($equipments as $equipment) {
            $responsibleUserId = $equipment->equipmentType?->responsible_user_id;

            if (blank($responsibleUserId) || blank($equipment->equipment_type_id)) {
                $skipped[] = $equipment->tag_number;
                $skipReasons[$equipment->tag_number] = 'Equipment Type belum punya Penanggung Jawab';

                continue;
            }

            $activities = Activity::query()
                ->where('equipment_type_id', $equipment->equipment_type_id)
                ->where('status', true)
                ->where('interval', $interval)
                ->orderBy('name')
                ->get();

            if ($activities->isEmpty()) {
                $skipped[] = $equipment->tag_number;
                $skipReasons[$equipment->tag_number] = 'Tidak ada checklist dengan interval "'.$interval.'"';

                continue;
            }

            $base = $this->firstWorkday(Carbon::today()->setTimeFromTimeString($startTime), $skipWeekend);

            for ($i = 0; $i < $quantity; $i++) {
                $startAt = $this->shiftByInterval($base, $interval, $i, $skipWeekend);

                DB::transaction(function () use ($equipment, $activities, $interval, $startAt, $issuedBy, $responsibleUserId): void {
                    $workOrder = WorkOrder::create([
                        'equipment_id' => $equipment->id,
                        'issued_by' => $issuedBy,
                        'technician_coordinator_id' => $responsibleUserId,
                        'classification' => 'Preventive',
                        'interval' => $interval,
                        'start_at' => $startAt,
                        'note' => 'Dibuat otomatis dari generate WO ('.$interval.')',
                        'status' => WorkOrder::STATUSES['Pending'],
                    ]);

                    foreach ($activities as $activity) {
                        WorkOrderActivity::create([
                            'work_order_id' => $workOrder->id,
                            'activity_id' => $activity->id,
                            'reference' => $activity->reference,
                            'unit' => $activity->unit,
                            'executed' => false,
                        ]);
                    }
                });

                $created++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped, 'skip_reasons' => $skipReasons];
    }

    /**
     * Geser tanggal dasar ke hari kerja pertama (mempertahankan jam).
     */
    protected function firstWorkday(Carbon $date, bool $skipWeekend): Carbon
    {
        $result = Workday::nextWorkday($date, $skipWeekend);

        return $result->copy()->setTimeFromTimeString($date->format('H:i'));
    }

    /**
     * Hitung tanggal WO ke-$index berdasarkan tanggal dasar + kelipatan interval,
     * lalu geser ke hari kerja bila perlu. Cadence tetap konsisten
     * (mis. 16 Nov → 16 Des → 16 Jan) dan bebas overflow akhir bulan.
     */
    protected function shiftByInterval(Carbon $base, string $interval, int $index, bool $skipWeekend): Carbon
    {
        if ($index <= 0) {
            return $this->firstWorkday($base->copy(), $skipWeekend);
        }

        if ($interval === self::DAILY_INTERVAL) {
            if (! $skipWeekend) {
                return $base->copy()->addDays($index);
            }

            // Tambah $index hari kerja (melewati Sabtu/Minggu) tanpa duplikasi.
            $date = $base->copy();
            $added = 0;

            while ($added < $index) {
                $date->addDay();

                if (! $date->isWeekend()) {
                    $added++;
                }
            }

            return $date;
        }

        $date = match ($interval) {
            'weekly' => $base->copy()->addWeeks($index),
            'monthly' => $base->copy()->addMonthsNoOverflow($index),
            'quarterly' => $base->copy()->addMonthsNoOverflow($index * 3),
            'semi_annual' => $base->copy()->addMonthsNoOverflow($index * 6),
            'yearly' => $base->copy()->addYearsNoOverflow($index),
            default => $base->copy()->addMonthsNoOverflow($index),
        };

        return $this->firstWorkday($date, $skipWeekend);
    }
}
