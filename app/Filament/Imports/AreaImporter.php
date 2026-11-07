<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ResolvesImportValues;
use App\Models\Area;
use App\Models\Company;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class AreaImporter extends Importer
{
    use ResolvesImportValues;

    protected static ?string $model = Area::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('company_id')
                ->label('Company')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): int|string|null => static::resolveModelId(Company::class, $state, ['name', 'code']))
                ->rules(['required', 'integer', 'exists:companies,id'])
                ->helperText('ID, nama, atau kode company. Contoh: 1, FT Tegal, FT-TGL.')
                ->example('FT-TGL'),
            ImportColumn::make('code')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->rules(['required', 'string', 'max:20'])
                ->helperText('Unik per company. Contoh: LOAD, STORAGE.')
                ->example('LOAD'),
            ImportColumn::make('name')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->rules(['required', 'string', 'max:100'])
                ->example('Loading'),
            ImportColumn::make('description')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string']),
            ImportColumn::make('latitude')
                ->numeric()
                ->ignoreBlankState()
                ->rules(['nullable', 'numeric', 'between:-90,90'])
                ->helperText('Opsional. Contoh: -6.8702557.'),
            ImportColumn::make('longitude')
                ->numeric()
                ->ignoreBlankState()
                ->rules(['nullable', 'numeric', 'between:-180,180'])
                ->helperText('Opsional. Contoh: 109.1867048.'),
            ImportColumn::make('is_active')
                ->label('Active')
                ->castStateUsing(fn (mixed $state): ?bool => $state === null ? null : static::parseImportBoolean($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'boolean'])
                ->helperText('1/0, true/false, active/inactive, aktif/nonaktif.')
                ->example('active'),
        ];
    }

    public function resolveRecord(): Area
    {
        $companyId = $this->data['company_id'] ?? null;
        $code = $this->data['code'] ?? null;

        if (blank($companyId) || blank($code)) {
            return new Area;
        }

        return Area::query()
            ->where('company_id', $companyId)
            ->whereRaw('LOWER(code) = ?', [mb_strtolower((string) $code)])
            ->first() ?? new Area;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import area selesai: '.Number::format($import->successful_rows).' baris berhasil diproses.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' baris gagal diproses.';
        }

        return $body;
    }
}
