<?php

namespace App\Support;

use App\Models\Setting;

class WorkOrderSettings
{
    public const AUTO_GENERATE = 'work_order.auto_generate';

    public const LEAD_DAYS = 'work_order.lead_days';

    public const START_TIME = 'work_order.start_time';

    public const SKIP_WEEKEND = 'work_order.skip_weekend';

    public const BACKFILL_DAYS = 'work_order.backfill_days';

    public const LEAD_DAYS_MIN = 1;

    public const LEAD_DAYS_MAX = 5;

    public const LABELS = [
        self::AUTO_GENERATE => 'Buat Work Order Otomatis',
        self::LEAD_DAYS => 'Buat WO untuk N Hari ke Depan',
        self::START_TIME => 'Jam Mulai WO',
        self::SKIP_WEEKEND => 'Lewati Sabtu & Minggu',
        self::BACKFILL_DAYS => 'Batas Backfill Jadwal Terlewat (hari)',
    ];

    public static function autoGenerate(): bool
    {
        return (bool) Setting::get(self::AUTO_GENERATE, true);
    }

    public static function leadDays(): int
    {
        $value = (int) Setting::get(self::LEAD_DAYS, 3);

        return max(self::LEAD_DAYS_MIN, min(self::LEAD_DAYS_MAX, $value));
    }

    public static function startTime(): string
    {
        $value = (string) Setting::get(self::START_TIME, '08:00');

        return preg_match('/^\d{2}:\d{2}$/', $value) === 1 ? $value : '08:00';
    }

    public static function skipWeekend(): bool
    {
        return (bool) Setting::get(self::SKIP_WEEKEND, true);
    }

    public static function backfillDays(): int
    {
        return max(0, (int) Setting::get(self::BACKFILL_DAYS, 14));
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return [
            'auto_generate' => static::autoGenerate(),
            'lead_days' => static::leadDays(),
            'start_time' => static::startTime(),
            'skip_weekend' => static::skipWeekend(),
            'backfill_days' => static::backfillDays(),
        ];
    }
}
