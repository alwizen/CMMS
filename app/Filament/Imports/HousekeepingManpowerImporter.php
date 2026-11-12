<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ResolvesImportValues;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingManpower;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class HousekeepingManpowerImporter extends Importer
{
    use ResolvesImportValues;

    protected static ?string $model = HousekeepingManpower::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('housekeeping_area_id')
                ->label('Area Housekeeping')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): int|string|null => static::resolveModelId(HousekeepingArea::class, $state, ['name', 'code']))
                ->rules(['required', 'integer', 'exists:housekeeping_areas,id'])
                ->helperText('ID, nama, atau kode area housekeeping. Contoh: 1, Taman Kantor, taman-kantor.')
                ->example('taman-kantor'),
            ImportColumn::make('name')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->rules(['required', 'string', 'max:100'])
                ->example('Budi Santoso'),
            ImportColumn::make('employee_number')
                ->label('Employee Number')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string', 'max:50'])
                ->helperText('Dipakai sebagai kunci update saat import ulang (per area).')
                ->example('EMP-001'),
            ImportColumn::make('phone')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string', 'max:30'])
                ->example('08123456789'),
            ImportColumn::make('is_active')
                ->label('Active')
                ->castStateUsing(fn (mixed $state): ?bool => $state === null ? null : static::parseImportBoolean($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'boolean'])
                ->helperText('1/0, true/false, active/inactive, aktif/nonaktif.')
                ->example('active'),
        ];
    }

    public function resolveRecord(): HousekeepingManpower
    {
        $areaId = $this->data['housekeeping_area_id'] ?? null;
        $employeeNumber = $this->data['employee_number'] ?? null;
        $name = $this->data['name'] ?? null;

        if (blank($areaId)) {
            return new HousekeepingManpower;
        }

        $query = HousekeepingManpower::query()->where('housekeeping_area_id', $areaId);

        if (filled($employeeNumber)) {
            $record = (clone $query)
                ->whereRaw('LOWER(employee_number) = ?', [mb_strtolower((string) $employeeNumber)])
                ->first();

            if ($record) {
                return $record;
            }
        }

        if (filled($name)) {
            return $query
                ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $name)])
                ->first() ?? new HousekeepingManpower;
        }

        return new HousekeepingManpower;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import manpower selesai: '.Number::format($import->successful_rows).' baris berhasil diproses.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' baris gagal diproses.';
        }

        return $body;
    }
}
