<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ResolvesImportValues;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingTask;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class HousekeepingTaskImporter extends Importer
{
    use ResolvesImportValues;

    protected static ?string $model = HousekeepingTask::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('housekeeping_facility_id')
                ->label('Fasilitas / Objek Area')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?int => static::resolveFacilityId($state))
                ->rules(['required', 'integer', 'exists:housekeeping_facilities,id'])
                ->helperText('Nama atau kode fasilitas. Contoh: Selokan Depan, Taman Parkir.')
                ->example('Selokan Depan'),
            ImportColumn::make('name')
                ->label('Name Checklist')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->rules(['required', 'string', 'max:150'])
                ->helperText('Nama pekerjaan. Dipakai bersama fasilitas sebagai kunci update.')
                ->example('Pembersihan Selokan'),
            ImportColumn::make('description')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string']),
            ImportColumn::make('is_active')
                ->label('Active')
                ->castStateUsing(fn (mixed $state): ?bool => $state === null ? null : static::parseImportBoolean($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'boolean'])
                ->helperText('1/0, true/false, active/inactive, aktif/nonaktif.')
                ->example('active'),
        ];
    }

    /**
     * Cari ID fasilitas berdasarkan nama atau kode.
     */
    protected static function resolveFacilityId(mixed $state): ?int
    {
        if (blank($state)) {
            return null;
        }

        if (is_int($state) || (is_string($state) && ctype_digit(trim($state)))) {
            $id = (int) $state;

            return HousekeepingFacility::query()->whereKey($id)->exists() ? $id : null;
        }

        $needle = mb_strtolower(trim((string) $state));

        if ($needle === '') {
            return null;
        }

        foreach (['name', 'code'] as $column) {
            $id = HousekeepingFacility::query()
                ->whereRaw('LOWER('.$column.') = ?', [$needle])
                ->value('id');

            if ($id !== null) {
                return (int) $id;
            }
        }

        return null;
    }

    public function resolveRecord(): HousekeepingTask
    {
        $facilityId = $this->data['housekeeping_facility_id'] ?? null;
        $name = $this->data['name'] ?? null;

        if (blank($facilityId) || blank($name)) {
            return new HousekeepingTask;
        }

        return HousekeepingTask::query()
            ->where('housekeeping_facility_id', $facilityId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $name)])
            ->first() ?? new HousekeepingTask;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import checklist housekeeping selesai: '.Number::format($import->successful_rows).' baris berhasil diproses.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' baris gagal diproses.';
        }

        return $body;
    }
}
