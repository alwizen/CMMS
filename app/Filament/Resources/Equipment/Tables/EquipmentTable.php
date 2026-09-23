<?php

namespace App\Filament\Resources\Equipment\Tables;

use App\Filament\Resources\Equipment\RelationManagers\MaintenanceHistoriesRelationManager;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Actions\RelationManagerAction;

class EquipmentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tag_number')
                    ->label('Tag Number')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn ($state): string => $state ?? '-'),
                TextColumn::make('area.name')
                    ->label('Area'),
                TextColumn::make('equipmentType.name')
                    ->label('Equipment Type')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'retired' => 'danger',
                    }),
                TextColumn::make('criticality')
                    ->label('Criticality')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'warning',
                        'high' => 'danger',
                        'critical' => 'danger',
                    }),
            ])
            ->filters([
                SelectFilter::make('area_id')
                    ->label('Area')
                    ->relationship('area', 'name'),
                SelectFilter::make('equipment_type_id')
                    ->label('Equipment Type')
                    ->relationship('equipmentType', 'name'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'retired' => 'Retired',
                    ]),
                SelectFilter::make('criticality')
                    ->label('Criticality')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ]),
            ])
            ->recordActions([
                RelationManagerAction::make('lesson-relation-manager')
                    ->label('View History')
                    ->icon(Heroicon::OutlinedClock)
                    ->compact()
                    ->relationManager(MaintenanceHistoriesRelationManager::class),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
