<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\ResolvesImportValues;
use App\Models\Activity;
use App\Models\EquipmentType;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ActivityImporter extends Importer
{
    use ResolvesImportValues;

    protected static ?string $model = Activity::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('equipment_type_id')
                ->label('Equipment Type')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): int|string|null => static::resolveModelId(EquipmentType::class, $state, ['name', 'code']))
                ->rules(['required', 'integer', 'exists:equipment_types,id'])
                ->helperText('ID, nama, atau kode equipment type. Contoh: 1, Pompa, pump.')
                ->example('pump'),
            ImportColumn::make('name')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->rules(['required', 'string', 'max:150'])
                ->helperText('Nama aktivitas. Kombinasi dengan equipment type dipakai sebagai kunci update.')
                ->example('Oil Change'),
            ImportColumn::make('type')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportKey($state))
                ->rules(['required', 'in:'.implode(',', array_keys(Activity::TYPES))])
                ->helperText(implode(' | ', array_keys(Activity::TYPES)))
                ->example('maintenance'),
            ImportColumn::make('maintenance_classification')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportKey($state))
                ->rules(['required', 'in:'.implode(',', array_keys(Activity::CLASSIFICATIONS))])
                ->helperText(implode(' | ', array_keys(Activity::CLASSIFICATIONS)))
                ->example('preventive'),
            ImportColumn::make('interval')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportKey($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'in:'.implode(',', array_keys(Activity::INTERVALS))])
                ->helperText(implode(' | ', array_keys(Activity::INTERVALS)).' (boleh kosong)')
                ->example('monthly'),
            ImportColumn::make('answer_type')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportKey($state))
                ->rules(['required', 'in:'.implode(',', array_keys(Activity::ANSWER_TYPES))])
                ->helperText(implode(' | ', array_keys(Activity::ANSWER_TYPES)))
                ->example('qualitative'),
            ImportColumn::make('reference')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string']),
            ImportColumn::make('optimum')
                ->numeric()
                ->ignoreBlankState()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('minimum')
                ->numeric()
                ->ignoreBlankState()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('maximum')
                ->numeric()
                ->ignoreBlankState()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('unit')
                ->castStateUsing(fn (mixed $state): ?string => static::normalizeImportText($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'string', 'max:30'])
                ->example('mm/s'),
            ImportColumn::make('status')
                ->castStateUsing(fn (mixed $state): ?bool => $state === null ? null : static::parseImportBoolean($state))
                ->ignoreBlankState()
                ->rules(['nullable', 'boolean'])
                ->helperText('1/0, true/false, active/inactive, aktif/nonaktif (default: aktif).')
                ->example('active'),
        ];
    }

    public function resolveRecord(): Activity
    {
        $equipmentTypeId = $this->data['equipment_type_id'] ?? null;
        $name = $this->data['name'] ?? null;

        if (blank($equipmentTypeId) || blank($name)) {
            return new Activity;
        }

        return Activity::query()
            ->where('equipment_type_id', $equipmentTypeId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $name)])
            ->first() ?? new Activity;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import activity selesai: '.Number::format($import->successful_rows).' baris berhasil diproses.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' baris gagal diproses.';
        }

        return $body;
    }
}
