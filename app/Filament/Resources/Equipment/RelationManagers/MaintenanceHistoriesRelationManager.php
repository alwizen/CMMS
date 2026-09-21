<?php

namespace App\Filament\Resources\Equipment\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenanceHistories';

    protected static ?string $title = 'Maintenance History';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('maintenance_type')
                    ->label('Maintenance Type')
                    ->required()
                    ->placeholder('e.g., Preventive Maintenance, Inspection'),
                TextInput::make('classification')
                    ->label('Classification')
                    ->required()
                    ->placeholder('e.g., Preventive, Corrective'),
                Textarea::make('description')
                    ->label('Description')
                    ->required()
                    ->placeholder('Describe the maintenance performed...'),
                DateTimePicker::make('maintenance_date')
                    ->label('Maintenance Date')
                    ->required(),
                TextInput::make('performed_by')
                    ->label('Performed By')
                    ->required()
                    ->placeholder('Technician name'),
                Textarea::make('findings')
                    ->label('Findings')
                    ->nullable()
                    ->placeholder('Enter findings from maintenance...'),
                Textarea::make('actions_taken')
                    ->label('Actions Taken')
                    ->nullable()
                    ->placeholder('Describe actions taken...'),
                TextInput::make('status')
                    ->label('Status')
                    ->required()
                    ->placeholder('e.g., Completed, In Progress'),
                TextInput::make('duration_minutes')
                    ->label('Duration (minutes)')
                    ->numeric()
                    ->nullable()
                    ->placeholder('e.g., 120'),
                Textarea::make('notes')
                    ->label('Notes')
                    ->nullable()
                    ->placeholder('Enter additional notes...'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('maintenance_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('classification')
                    ->label('Classification')
                    ->sortable(),
                TextColumn::make('maintenance_date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('performed_by')
                    ->label('Performed By')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('classification')
                    ->label('Classification')
                    ->options([
                        'preventive' => 'Preventive',
                        'corrective' => 'Corrective',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'completed' => 'Completed',
                        'in_progress' => 'In Progress',
                        'pending' => 'Pending',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
