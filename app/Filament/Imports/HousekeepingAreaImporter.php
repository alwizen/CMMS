<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ResolvesImportValues;
use App\Models\Company;
use App\Models\HousekeepingArea;
use App\Support\Code;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class HousekeepingAreaImporter extends Importer
{
    use ResolvesImportValues;

    protected static ?string $model = HousekeepingArea::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('company_id')
                ->label('Company')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): int|string|null => static::resolveModelId(Company::class, $state, ['name', 'code']))
                ->rules(['required', 'integer', 'exists:companies,id'])
                ->helperText('ID, nama, atau kode company. Contoh: 1, FT Tegal, ft-tegal.')
                ->example('ft-tegal'),
            ImportColumn::make('name')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->rules(['required', 'string', 'max:100'])
                ->helperText('Nama area. Contoh: Taman Kantor, Area Parkir.')
                ->example('Taman Kantor'),
            ImportColumn::make('code')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string', 'max:20'])
                ->helperText('Opsional. Bila kosong akan dibuat otomatis dari Name (unik per company).')
                ->example('taman-kantor'),
            ImportColumn::make('description')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string'])
                ->example('Area taman depan kantor'),
            ImportColumn::make('is_active')
                ->label('Active')
                ->castStateUsing(fn (mixed $state): ?bool => $state === null ? null : static::parseImportBoolean($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'boolean'])
                ->helperText('1/0, true/false, active/inactive, aktif/nonaktif.')
                ->example('active'),
        ];
    }

    public function resolveRecord(): HousekeepingArea
    {
        $companyId = $this->data['company_id'] ?? null;
        $code = $this->data['code'] ?? null;
        $name = $this->data['name'] ?? null;

        if (blank($companyId)) {
            return new HousekeepingArea;
        }

        // Kunci update: kode bila ada, jika tidak gunakan nama (per company).
        if (filled($code)) {
            $record = HousekeepingArea::query()
                ->where('company_id', $companyId)
                ->whereRaw('LOWER(code) = ?', [mb_strtolower((string) $code)])
                ->first();

            if ($record) {
                return $record;
            }
        }

        if (filled($name)) {
            return HousekeepingArea::query()
                ->where('company_id', $companyId)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $name)])
                ->first() ?? new HousekeepingArea;
        }

        return new HousekeepingArea;
    }

    /**
     * Lengkapi kode otomatis (bila kosong) sebelum data diisikan ke record.
     */
    protected function beforeFill(): void
    {
        if (blank($this->data['code'] ?? null)) {
            $this->data['code'] = Code::fromName($this->data['name'] ?? '');
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import area housekeeping selesai: '.Number::format($import->successful_rows).' baris berhasil diproses.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' baris gagal diproses.';
        }

        return $body;
    }
}
