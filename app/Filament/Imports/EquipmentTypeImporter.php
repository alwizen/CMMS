<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ResolvesImportValues;
use App\Models\EquipmentType;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class EquipmentTypeImporter extends Importer
{
    use ResolvesImportValues;

    protected static ?string $model = EquipmentType::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('code')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->rules(['required', 'string', 'max:50'])
                ->helperText('Unik. Dipakai sebagai kunci update saat import ulang. Contoh: pump, tank.')
                ->example('pump'),
            ImportColumn::make('name')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->rules(['required', 'string', 'max:100'])
                ->example('Pompa'),
            ImportColumn::make('description')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string']),
            ImportColumn::make('status')
                ->castStateUsing(fn (mixed $state): ?bool => $state === null ? null : static::parseImportBoolean($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'boolean'])
                ->helperText('1/0, true/false, active/inactive, aktif/nonaktif (default: aktif).')
                ->example('active'),
        ];
    }

    public function resolveRecord(): EquipmentType
    {
        $code = $this->data['code'] ?? null;

        if (blank($code)) {
            return new EquipmentType;
        }

        return EquipmentType::query()
            ->whereRaw('LOWER(code) = ?', [mb_strtolower((string) $code)])
            ->first() ?? new EquipmentType;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import equipment type selesai: '.Number::format($import->successful_rows).' baris berhasil diproses.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' baris gagal diproses.';
        }

        return $body;
    }
}
