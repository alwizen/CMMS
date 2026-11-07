<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ResolvesImportValues;
use App\Models\Area;
use App\Models\Equipment;
use App\Models\EquipmentType;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class EquipmentImporter extends Importer
{
    use ResolvesImportValues;

    protected static ?string $model = Equipment::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('area_id')
                ->label('Area')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): int|string|null => static::resolveModelId(Area::class, $state, ['name', 'code']))
                ->rules(['required', 'integer', 'exists:areas,id'])
                ->helperText('ID, nama, atau kode area. Contoh: 1, Loading, LOAD.')
                ->example('LOAD'),
            ImportColumn::make('tag_number')
                ->label('Tag Number')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->rules(['required', 'string', 'max:50'])
                ->helperText('Unik. Dipakai sebagai kunci update saat import ulang.')
                ->example('P-101'),
            ImportColumn::make('description')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string']),
            ImportColumn::make('equipment_type_id')
                ->label('Equipment Type')
                ->castStateUsing(fn (mixed $state): int|string|null => static::resolveModelId(EquipmentType::class, $state, ['name', 'code']))
                ->ignoreBlankState()
                ->rules(['nullable', 'integer', 'exists:equipment_types,id'])
                ->helperText('ID, nama, atau kode equipment type. Contoh: 1, Pompa, pump.')
                ->example('pump'),
            ImportColumn::make('manufacturer')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string', 'max:100']),
            ImportColumn::make('model')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string', 'max:100']),
            ImportColumn::make('serial_number')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string', 'max:100']),
            ImportColumn::make('installation_date')
                ->ignoreBlankState()
                ->rules(['nullable', 'date'])
                ->helperText('Format: YYYY-MM-DD. Contoh: 2024-05-01.')
                ->example('2024-05-01'),
            ImportColumn::make('operational_unit')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string', 'max:20'])
                ->helperText('Contoh: hour, day, km.'),
            ImportColumn::make('photo')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string', 'max:255'])
                ->helperText('Path file foto (opsional).'),
            ImportColumn::make('status')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->rules(['required', 'string', 'in:active,inactive,retired'])
                ->helperText('active | inactive | retired')
                ->example('active'),
            ImportColumn::make('criticality')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->rules(['required', 'string', 'in:low,medium,high,critical'])
                ->helperText('low | medium | high | critical')
                ->example('medium'),
        ];
    }

    public function resolveRecord(): Equipment
    {
        $tagNumber = $this->data['tag_number'] ?? null;

        if (blank($tagNumber)) {
            return new Equipment;
        }

        return Equipment::query()
            ->whereRaw('LOWER(tag_number) = ?', [mb_strtolower((string) $tagNumber)])
            ->first() ?? new Equipment;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import equipment selesai: '.Number::format($import->successful_rows).' baris berhasil diproses.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' baris gagal diproses.';
        }

        return $body;
    }
}
