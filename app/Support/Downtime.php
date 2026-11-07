<?php

namespace App\Support;

class Downtime
{
    public static function formatMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return '-';
        }

        if ($minutes < 60) {
            return $minutes.' menit';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        if ($remaining === 0) {
            return $hours.' jam';
        }

        return $hours.' jam '.$remaining.' menit';
    }

    public static function formatHours(?int $minutes): string
    {
        return number_format(((int) $minutes) / 60, 1, ',', '.').' jam';
    }

    /**
     * @param  iterable<int, object>  $records
     */
    public static function totalMinutes(iterable $records): int
    {
        $total = 0;

        foreach ($records as $record) {
            if (! method_exists($record, 'downtimeMinutes')) {
                continue;
            }

            $total += (int) ($record->downtimeMinutes() ?? 0);
        }

        return $total;
    }

    /**
     * @param  iterable<int, object>  $records
     */
    public static function eventCount(iterable $records): int
    {
        $count = 0;

        foreach ($records as $record) {
            if (! method_exists($record, 'downtimeMinutes')) {
                continue;
            }

            if ($record->downtimeMinutes() !== null) {
                $count++;
            }
        }

        return $count;
    }
}
