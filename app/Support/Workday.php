<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class Workday
{
    public static function isWorkday(CarbonInterface $date, bool $skipWeekend = true): bool
    {
        if (! $skipWeekend) {
            return true;
        }

        return ! $date->isWeekend();
    }

    public static function nextWorkday(CarbonInterface $date, bool $skipWeekend = true): Carbon
    {
        $cursor = Carbon::parse($date->toDateString());

        while (! static::isWorkday($cursor, $skipWeekend)) {
            $cursor->addDay();
        }

        return $cursor;
    }

    /**
     * @return list<Carbon>
     */
    public static function workdaysBetween(CarbonInterface $from, CarbonInterface $to, bool $skipWeekend = true): array
    {
        $cursor = Carbon::parse($from->toDateString());
        $end = Carbon::parse($to->toDateString());

        $days = [];

        while ($cursor->lte($end)) {
            if (static::isWorkday($cursor, $skipWeekend)) {
                $days[] = $cursor->copy();
            }

            $cursor->addDay();
        }

        return $days;
    }
}
