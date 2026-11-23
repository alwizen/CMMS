<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\User;
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
     * WO dimiliki oleh FUNGSI (shared pool): semua user aktif pada fungsi itu
     * dapat melihat & mengerjakan WO. `technician_coordinator_id` diisi nanti
     * oleh user yang pertama kali mulai mengerjakan (performed by).
     *
     * Syarat per equipment:
     * - Harus ada minimal satu user aktif pada fungsi yang dipilih.
     * - Tipe equipment harus punya activity aktif dengan interval yang dipilih.
     * Equipment yang tidak memenuhi syarat dilewati.
     *
     * WO duplikat (equipment + interval sama pada periode yang sama) dilewati
     * kecuali $allowDuplicate = true.
     *
     * @param  array<int, int>  $equipmentIds
     * @return array{created: int, skipped: array<int, string>, skip_reasons: array<string, string>, duplicates: array<string, string>}
     */
    public function generateDirect(
        array $equipmentIds,
        string $interval,
        int $quantity,
        string $startTime,
        bool $skipWeekend,
        int $issuedBy,
        ?string $startDate = null,
        int $expiredDays = 0,
        ?string $fungsi = null,
        bool $allowDuplicate = false,
    ): array {
        $interval = strtolower(trim($interval));
        $quantity = max(1, $quantity);
        $expiredDays = max(0, $expiredDays);

        $startDay = filled($startDate)
            ? Carbon::parse($startDate)->startOfDay()
            : Carbon::today();

        $equipments = Equipment::query()
            ->with('equipmentType.responsibleUsers')
            ->whereIn('id', $equipmentIds)
            ->get();

        $fungsi = filled($fungsi) ? trim((string) $fungsi) : null;

        $fungsiHasActiveUser = $fungsi !== null
            && User::query()
                ->where('status', true)
                ->where('fungsi', $fungsi)
                ->exists();

        $created = 0;
        $skipped = [];
        $skipReasons = [];
        $duplicates = [];

        foreach ($equipments as $equipment) {
            // WO dimiliki fungsi; koordinator diisi saat pengerjaan (performed by).
            $woFungsi = $fungsi ?? $equipment->equipmentType?->fungsi;

            if (blank($equipment->equipment_type_id) || blank($woFungsi)) {
                $skipped[] = $equipment->tag_number;
                $skipReasons[$equipment->tag_number] = 'Equipment Type belum punya Fungsi';

                continue;
            }

            if ($fungsi !== null && ! $fungsiHasActiveUser) {
                $skipped[] = $equipment->tag_number;
                $skipReasons[$equipment->tag_number] = 'Belum ada user aktif dengan fungsi '.$fungsi;

                continue;
            }

            if (! User::query()->where('status', true)->where('fungsi', $woFungsi)->exists()) {
                $skipped[] = $equipment->tag_number;
                $skipReasons[$equipment->tag_number] = 'Belum ada user aktif dengan fungsi '.$woFungsi;

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

            $base = $this->firstWorkday($startDay->copy()->setTimeFromTimeString($startTime), $skipWeekend);

            $equipmentCreated = 0;
            $duplicateMessage = null;

            for ($i = 0; $i < $quantity; $i++) {
                $startAt = $this->shiftByInterval($base, $interval, $i, $skipWeekend);

                if (! $allowDuplicate && $this->hasDuplicate($equipment->id, $interval, $startAt, $woFungsi)) {
                    $duplicateMessage = 'Sudah ada WO interval '.$interval.' pada '.$this->periodLabel($startAt, $interval);

                    continue;
                }

                $expiredAt = $expiredDays > 0 ? $startAt->copy()->addDays($expiredDays) : null;

                DB::transaction(function () use ($equipment, $activities, $interval, $startAt, $expiredDays, $expiredAt, $issuedBy, $woFungsi): void {
                    $workOrder = WorkOrder::create([
                        'equipment_id' => $equipment->id,
                        'issued_by' => $issuedBy,
                        'technician_coordinator_id' => null,
                        'fungsi' => $woFungsi,
                        'classification' => 'Preventive',
                        'interval' => $interval,
                        'start_at' => $startAt,
                        'expired_days' => $expiredDays > 0 ? $expiredDays : null,
                        'expired_at' => $expiredAt,
                        'note' => 'Dibuat otomatis dari generate WO ('.$interval.')',
                        'status' => WorkOrder::STATUSES['Pending'],
                    ]);

                    foreach ($activities as $activity) {
                        WorkOrderActivity::create([
                            'work_order_id' => $workOrder->id,
                            'activity_id' => $activity->id,
                            'name' => $activity->name,
                            'reference' => $activity->reference,
                            'unit' => $activity->unit,
                            'executed' => false,
                        ]);
                    }
                });

                $created++;
                $equipmentCreated++;
            }

            if ($duplicateMessage !== null) {
                $duplicates[$equipment->tag_number] = $duplicateMessage;

                if ($equipmentCreated === 0) {
                    $skipped[] = $equipment->tag_number;
                    $skipReasons[$equipment->tag_number] = $duplicateMessage;
                }
            }
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
            'skip_reasons' => $skipReasons,
            'duplicates' => $duplicates,
        ];
    }

    /**
     * Buat Work Order otomatis untuk rentang tanggal tertentu (dipakai scheduler).
     *
     * Untuk setiap Equipment Type aktif yang punya fungsi & checklist aktif,
     * dibuat satu WO per equipment per interval per periode (harian/mingguan/
     * bulanan/…). Periode yang sudah punya WO dilewati, sehingga aman dijalankan
     * berulang kali.
     *
     * @return array{created: int, existing: int, types: int, equipment: int}
     */
    public function generateScheduled(Carbon $from, Carbon $to, ?int $issuedBy = null): array
    {
        $settings = WorkOrderSettings::all();
        $skipWeekend = (bool) $settings['skip_weekend'];
        $startTime = (string) $settings['start_time'];
        $expiredDays = (int) $settings['expired_days'];
        $issuedBy ??= SystemUser::id();

        $result = [
            'created' => 0,
            'existing' => 0,
            'types' => 0,
            'equipment' => 0,
        ];

        $workdays = Workday::workdaysBetween($from, $to, $skipWeekend);

        if ($workdays === []) {
            return $result;
        }

        $types = EquipmentType::query()
            ->where('status', true)
            ->whereNotNull('fungsi')
            ->whereHas('activities', fn ($query) => $query->where('status', true)->whereNotNull('interval'))
            ->whereHas('equipment')
            ->with([
                'activities' => fn ($query) => $query->where('status', true)->whereNotNull('interval')->orderBy('name'),
                'equipment',
            ])
            ->get();

        foreach ($types as $type) {
            $fungsi = trim((string) $type->fungsi);

            if ($fungsi === '' || ! User::query()->where('status', true)->where('fungsi', $fungsi)->exists()) {
                continue;
            }

            $intervals = $type->activities->pluck('interval')->unique()->values();

            if ($intervals->isEmpty()) {
                continue;
            }

            $result['types']++;

            foreach ($type->equipment as $equipment) {
                $result['equipment']++;

                foreach ($intervals as $interval) {
                    $activities = $type->activities->where('interval', $interval)->values();

                    if ($activities->isEmpty()) {
                        continue;
                    }

                    foreach ($workdays as $day) {
                        $startAt = $day->copy()->setTimeFromTimeString($startTime);

                        if ($this->hasDuplicate($equipment->id, $interval, $startAt, $fungsi)) {
                            $result['existing']++;

                            continue;
                        }

                        DB::transaction(function () use ($equipment, $activities, $interval, $startAt, $expiredDays, $issuedBy, $fungsi): void {
                            $workOrder = WorkOrder::create([
                                'equipment_id' => $equipment->id,
                                'issued_by' => $issuedBy,
                                'technician_coordinator_id' => null,
                                'fungsi' => $fungsi,
                                'classification' => 'Preventive',
                                'interval' => $interval,
                                'start_at' => $startAt,
                                'expired_days' => $expiredDays > 0 ? $expiredDays : null,
                                'expired_at' => $expiredDays > 0 ? $startAt->copy()->addDays($expiredDays) : null,
                                'note' => 'Dibuat otomatis oleh scheduler ('.$interval.')',
                                'status' => WorkOrder::STATUSES['Pending'],
                            ]);

                            foreach ($activities as $activity) {
                                WorkOrderActivity::create([
                                    'work_order_id' => $workOrder->id,
                                    'activity_id' => $activity->id,
                                    'name' => $activity->name,
                                    'reference' => $activity->reference,
                                    'unit' => $activity->unit,
                                    'executed' => false,
                                ]);
                            }
                        });

                        $result['created']++;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Apakah sudah ada WO (non-cancelled) untuk equipment + interval yang sama
     * pada periode yang sama dengan tanggal rencana.
     */
    protected function hasDuplicate(int $equipmentId, string $interval, Carbon $startAt, ?string $fungsi = null): bool
    {
        [$from, $to] = $this->periodBounds($startAt, $interval);

        return WorkOrder::query()
            ->where('equipment_id', $equipmentId)
            ->where('interval', $interval)
            ->when(filled($fungsi), fn ($query) => $query->where('fungsi', $fungsi))
            ->whereNotIn('status', [WorkOrder::STATUSES['Cancelled']])
            ->whereBetween('start_at', [$from, $to])
            ->exists();
    }

    /**
     * Batas periode untuk pengecekan duplikat sesuai interval.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function periodBounds(Carbon $date, string $interval): array
    {
        $start = match ($interval) {
            'weekly' => $date->copy()->startOfWeek(),
            'monthly' => $date->copy()->startOfMonth(),
            'quarterly' => $date->copy()->startOfQuarter(),
            'semi_annual' => $date->copy()->startOfYear()->addMonthsNoOverflow($date->month <= 6 ? 0 : 6),
            'yearly' => $date->copy()->startOfYear(),
            default => $date->copy()->startOfDay(),
        };

        $end = match ($interval) {
            'weekly' => $date->copy()->endOfWeek(),
            'monthly' => $date->copy()->endOfMonth(),
            'quarterly' => $date->copy()->endOfQuarter(),
            'semi_annual' => $start->copy()->addMonthsNoOverflow(6)->subSecond(),
            'yearly' => $date->copy()->endOfYear(),
            default => $date->copy()->endOfDay(),
        };

        return [$start, $end];
    }

    protected function periodLabel(Carbon $date, string $interval): string
    {
        return match ($interval) {
            'weekly' => 'minggu '.$date->isoFormat('W, GGGG'),
            'monthly' => $date->isoFormat('MMMM YYYY'),
            'quarterly' => 'Kuartal '.$date->quarter.' '.$date->year,
            'semi_annual' => 'Semester '.($date->month <= 6 ? '1' : '2').' '.$date->year,
            'yearly' => 'Tahun '.$date->year,
            default => $date->isoFormat('D MMMM YYYY'),
        };
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
