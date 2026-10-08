<?php

namespace App\Filament\Imports;

use App\Models\Activity;
use App\Models\EquipmentType;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ActivityImporter extends Importer
{
    protected static ?string $model = Activity::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('equipment_type_id')
                ->label('Equipment Type (nama)')
                ->requiredMapping()
                ->relationship(
                    name: 'equipmentType',
                    resolveUsing: function (mixed $state): ?EquipmentType {
                        return EquipmentType::query()
                            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $state))])
                            ->first();
                    },
                )
                ->rules(['required'])
                ->helperText('Nama equipment type persis seperti di master data (case-insensitive).'),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:150']),
            ImportColumn::make('type')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): string => self::normalizeKey($state))
                ->rules(['required', 'in:'.implode(',', array_keys(Activity::TYPES))])
                ->helperText(implode(' | ', array_keys(Activity::TYPES))),
            ImportColumn::make('maintenance_classification')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): string => self::normalizeKey($state))
                ->rules(['required', 'in:'.implode(',', array_keys(Activity::CLASSIFICATIONS))])
                ->helperText(implode(' | ', array_keys(Activity::CLASSIFICATIONS))),
            ImportColumn::make('interval')
                ->castStateUsing(fn (mixed $state): ?string => filled($state) ? self::normalizeKey($state) : null)
                ->rules(['nullable', 'in:'.implode(',', array_keys(Activity::INTERVALS))])
                ->helperText(implode(' | ', array_keys(Activity::INTERVALS)).' (boleh kosong)'),
            ImportColumn::make('answer_type')
                ->requiredMapping()
                ->castStateUsing(fn (mixed $state): string => ucfirst(mb_strtolower(trim((string) $state))))
                ->rules(['required', 'string', 'max:30'])
                ->helperText('qualitative | quantitative'),
            ImportColumn::make('reference')
                ->rules(['nullable', 'string']),
            ImportColumn::make('optimum')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('minimum')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('maximum')
                ->numeric()
                ->rules(['nullable', 'numeric']),
            ImportColumn::make('unit')
                ->rules(['nullable', 'string', 'max:30']),
            ImportColumn::make('status')
                ->boolean()
                ->rules(['nullable', 'boolean'])
                ->helperText('1/0, true/false, active/inactive (default: aktif)'),
        ];
    }

    public function resolveRecord(): Activity
    {
        return new Activity;
    }

    public function mutateBeforeCreate(): array
    {
        $data = $this->data;

        $data['status'] = $this->normalizeStatus($data['status'] ?? null);

        return $data;
    }

    protected function normalizeStatus(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (is_bool($value)) {
            return $value;
        }

        $value = mb_strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'yes', 'ya', 'active', 'aktif', 'on'], true);
    }

    /**
     * Normalisasi input seperti "Visual Check" / "visual check" menjadi "visual_check".
     */
    protected static function normalizeKey(mixed $state): string
    {
        $value = mb_strtolower(trim((string) $state));

        return str_replace([' ', '-'], '_', $value);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Activity import selesai: '.Number::format($import->successful_rows).' baris berhasil diimport.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' baris gagal diimport.';
        }

        return $body;
    }
}
