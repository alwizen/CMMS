<?php

namespace App\Filament\Resources\HousekeepingManpowers\Schemas;

use App\Models\HousekeepingArea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HousekeepingManpowerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Manpower Information')
                    ->columnSpanFull()
                    ->description('Manpower adalah petugas housekeeping dan terpisah dari akun user. Manpower hanya fokus pada area yang dipilih.')
                    ->schema([
                        Select::make('housekeeping_area_id')
                            ->label('Area Housekeeping')
                            ->options(fn (): array => HousekeepingArea::query()
                                ->where('is_active', true)
                                ->with('company:id,name')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (HousekeepingArea $area): array => [
                                    $area->id => $area->name.($area->company ? ' — '.$area->company->name : ''),
                                ])
                                ->all())
                            ->searchable()
                            ->required()
                            ->placeholder('Select area'),
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->placeholder('e.g., Budi Santoso'),
                        TextInput::make('employee_number')
                            ->label('Employee Number')
                            ->nullable()
                            ->placeholder('e.g., EMP-001'),
                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->nullable()
                            ->placeholder('e.g., 08123456789'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
