<?php

namespace App\Support;

use App\Models\DocumentSequence;
use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentNumber
{
    public const SETTING_PREFIX = 'numbering';

    /**
     * Format default mengikuti perilaku penomoran sebelumnya.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'work_order' => 'WO-{YYYY}{MM}{DD}-{SEQ}',
        'maintenance_plan' => 'MP-{YYYY}{MM}-{SEQ}',
        'maintenance_schedule' => 'MS-{YYYY}{MM}-{SEQ}',
        'maintenance_request' => 'MR-{YYYY}{MM}{DD}-{SEQ}',
        'document' => 'DOC-{YYYY}{MM}-{SEQ}',
    ];

    /**
     * @var array<string, string>
     */
    public const LABELS = [
        'work_order' => 'Work Order',
        'maintenance_plan' => 'Maintenance Plan',
        'maintenance_schedule' => 'Maintenance Schedule',
        'maintenance_request' => 'Maintenance Request',
        'document' => 'Document',
    ];

    public static function format(string $type): string
    {
        $format = Setting::get(static::settingKey($type));

        if (! is_string($format) || trim($format) === '' || ! static::hasSequenceToken($format)) {
            return static::DEFAULTS[$type] ?? '{SEQ}';
        }

        return $format;
    }

    public static function setFormat(string $type, string $format): void
    {
        Setting::set(static::settingKey($type), trim($format));
    }

    public static function settingKey(string $type): string
    {
        return static::SETTING_PREFIX.'.'.$type.'.format';
    }

    public static function hasSequenceToken(string $format): bool
    {
        return preg_match('/\{SEQ(?::\d+)?\}/', $format) === 1;
    }

    /**
     * Ambil nomor berikutnya secara atomik per prefix (aman untuk request bersamaan).
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function generate(string $type, string $modelClass, string $column): string
    {
        [$prefix, $width, $suffix] = static::split(static::format($type));

        $next = static::nextAtomic($prefix);

        if ($next <= static::legacyMax($modelClass, $column, $prefix)) {
            $next = static::bumpSequence($prefix, static::legacyMax($modelClass, $column, $prefix));
        }

        return $prefix.str_pad((string) $next, $width, '0', STR_PAD_LEFT).$suffix;
    }

    /**
     * Contoh hasil format untuk ditampilkan sebagai preview.
     */
    public static function preview(string $format, int $sequence = 1, ?CarbonInterface $date = null): string
    {
        if (! static::hasSequenceToken($format)) {
            return static::resolveTokens($format, $date ?? Carbon::now());
        }

        [$prefix, $width, $suffix] = static::split($format, $date);

        return $prefix.str_pad((string) $sequence, $width, '0', STR_PAD_LEFT).$suffix;
    }

    /**
     * @return array{0: string, 1: int, 2: string}
     */
    protected static function split(string $format, ?CarbonInterface $date = null): array
    {
        $date ??= Carbon::now();

        preg_match('/\{SEQ(?::(\d+))?\}/', $format, $matches);

        if ($matches === []) {
            return [static::resolveTokens($format, $date), 4, ''];
        }

        $width = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : 4;
        $position = strpos($format, $matches[0]);

        $prefix = static::resolveTokens(substr($format, 0, $position), $date);
        $suffix = static::resolveTokens(substr($format, $position + strlen($matches[0])), $date);

        return [$prefix, max($width, 1), $suffix];
    }

    protected static function resolveTokens(string $value, CarbonInterface $date): string
    {
        return str_replace(
            ['{YYYY}', '{YY}', '{MM}', '{MONTH}', '{DD}', '{DATE}'],
            [
                $date->format('Y'),
                $date->format('y'),
                $date->format('m'),
                $date->format('M'),
                $date->format('d'),
                $date->format('Ymd'),
            ],
            $value,
        );
    }

    /**
     * Increment counter per prefix di dalam transaksi dengan baris terkunci.
     */
    protected static function nextAtomic(string $prefix): int
    {
        return DB::transaction(function () use ($prefix): int {
            $row = DocumentSequence::query()->lockForUpdate()->firstOrCreate(
                ['prefix' => $prefix],
                ['last_number' => 0],
            );

            $row->last_number++;
            $row->save();

            return $row->last_number;
        });
    }

    /**
     * Sinkronisasi counter bila data lama sudah melampaui counter tersimpan.
     */
    protected static function bumpSequence(string $prefix, int $floor): int
    {
        return DB::transaction(function () use ($prefix, $floor): int {
            $row = DocumentSequence::query()->lockForUpdate()->firstOrCreate(
                ['prefix' => $prefix],
                ['last_number' => 0],
            );

            $row->last_number = max($row->last_number, $floor) + 1;
            $row->save();

            return $row->last_number;
        });
    }

    /**
     * Nilai tertinggi dari data yang sudah ada, untuk kompatibilitas nomor lama.
     *
     * @param  class-string<Model>  $modelClass
     */
    protected static function legacyMax(string $modelClass, string $column, string $prefix): int
    {
        $pattern = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $prefix).'%';

        $max = 0;

        foreach ($modelClass::query()->where($column, 'like', $pattern)->pluck($column) as $number) {
            $remainder = substr((string) $number, strlen($prefix));

            if (preg_match('/^(\d+)/', $remainder, $matches) === 1) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $max;
    }
}
