<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class EquipmentHealthWidget extends BaseWidget
{
    protected static ?string $heading = 'Kondisi Equipment';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(Equipment::with('area')->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('tag_number')
                    ->label('Tag')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Equipment')
                    ->searchable(),
                Tables\Columns\TextColumn::make('area.name')
                    ->label('Area'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'retired' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('criticality')
                    ->label('Criticality')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'low' => 'info',
                        'medium' => 'warning',
                        'high' => 'danger',
                        'critical' => 'danger',
                        default => 'gray',
                    }),
            ]);
    }
}
