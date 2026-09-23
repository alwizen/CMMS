<?php

namespace App\Filament\Imports;

use App\Models\Equipment;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class EquipmentImporter extends Importer
{
    protected static ?string $model = Equipment::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('area_id')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('tag_number')
                ->requiredMapping()
                ->rules(['required', 'max:50']),
            ImportColumn::make('description'),
            ImportColumn::make('equipment_type_id')
                ->rules(['max:100']),
            ImportColumn::make('manufacturer')
                ->rules(['max:100']),
            ImportColumn::make('model')
                ->rules(['max:100']),
            ImportColumn::make('serial_number')
                ->rules(['max:100']),
            ImportColumn::make('installation_date')
                ->rules(['nullable', 'date']),
            ImportColumn::make('operational_unit')
                ->rules(['max:20']),
            ImportColumn::make('photo')
                ->rules(['max:255']),
            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(['required', 'max:30']),
            ImportColumn::make('criticality')
                ->requiredMapping()
                ->rules(['required', 'max:20']),
        ];
    }

    public function resolveRecord(): Equipment
    {
        return new Equipment();
    }

    public function mutateBeforeCreate(): array
    {
        $data = $this->data;

        $data['area_id'] = $data['area'] ?? null;
        unset($data['area']);

        return $data;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your equipment import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
