<?php

namespace App\Filament\App\Resources\Equipment\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MaintenanceHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenanceHistories';

    protected static ?string $title = 'Maintenance History';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('maintenance_date', 'desc')
            ->columns([
                TextColumn::make('classification')
                    ->label('Classification')
                    ->sortable(),
                TextColumn::make('maintenance_date')
                    ->label('Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('performedBy.name')
                    ->label('Performed By')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'completed' => 'success',
                        'in progress', 'in_progress' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->placeholder('-'),
                TextColumn::make('findings')
                    ->label('Findings')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('actions_taken')
                    ->label('Actions Taken')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('classification')
                    ->label('Classification')
                    ->options([
                        'Preventive' => 'Preventive',
                        'Corrective' => 'Corrective',
                    ]),
            ]);
    }
}
