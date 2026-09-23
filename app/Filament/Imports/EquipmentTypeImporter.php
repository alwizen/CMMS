<?php

namespace App\Filament\Imports;

use App\Models\EquipmentType;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class EquipmentTypeImporter extends Importer
{
    protected static ?string $model = EquipmentType::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('code')
                ->requiredMapping()
                ->rules(['required', 'max:50']),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:100']),
            ImportColumn::make('description')
                ->rules(['nullable']),
            ImportColumn::make('status')
                ->rules(['nullable', 'boolean']),
        ];
    }

    public function resolveRecord(): ?EquipmentType
    {
        return new EquipmentType();
    }

    public function mutateBeforeCreate(): array
    {
        $data = $this->data;

        if (isset($data['status'])) {
            $data['status'] = filter_var($data['status'], FILTER_VALIDATE_BOOLEAN);
        }

        return $data;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your equipment type import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
