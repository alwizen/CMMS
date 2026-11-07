<?php

namespace App\Filament\Imports\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesImportValues
{
    /**
     * Resolve a foreign key from an ID, code, or name.
     *
     * Returns the original string when it cannot be resolved so that the
     * `exists` validation rule fails instead of silently clearing the value.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int, string>  $columns  Columns checked for name/code matching, in priority order.
     */
    protected static function resolveModelId(string $modelClass, mixed $state, array $columns = ['name', 'code']): int|string|null
    {
        if (blank($state)) {
            return null;
        }

        if (is_int($state) || is_float($state)) {
            return (int) $state;
        }

        $value = trim((string) $state);

        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            $id = (int) $value;

            if ($modelClass::query()->whereKey($id)->exists()) {
                return $id;
            }
        }

        $needle = mb_strtolower($value);

        foreach ($columns as $column) {
            $match = $modelClass::query()
                ->whereRaw('LOWER('.$column.') = ?', [$needle])
                ->value(app($modelClass)->getKeyName());

            if ($match !== null) {
                return (int) $match;
            }
        }

        return $value;
    }

    /**
     * Parse common boolean representations used in spreadsheets.
     */
    protected static function parseImportBoolean(mixed $state, bool $default = true): ?bool
    {
        if ($state === null || $state === '') {
            return $default;
        }

        if (is_bool($state)) {
            return $state;
        }

        $value = mb_strtolower(trim((string) $state));

        return match ($value) {
            '1', 'true', 'yes', 'y', 'on', 'active', 'aktif' => true,
            '0', 'false', 'no', 'n', 'off', 'inactive', 'nonaktif', 'tidak aktif' => false,
            default => $default,
        };
    }

    /**
     * Normalize keys such as "Visual Check" or "visual-check" into "visual_check".
     */
    protected static function normalizeImportKey(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $value = mb_strtolower(trim((string) $state));

        return str_replace([' ', '-'], '_', $value);
    }

    protected static function normalizeImportText(mixed $state): ?string
    {
        if ($state === null) {
            return null;
        }

        $value = trim((string) $state);

        return $value === '' ? null : $value;
    }
}
