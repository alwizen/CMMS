<?php

namespace App\Filament\Resources\Areas\Tables;

use App\Filament\Resources\Areas\AreaResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AreasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50),
                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Status'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view_equipment')
                    ->label('Lihat Equipment')
                    ->icon('heroicon-m-wrench-screwdriver')
                    ->url(fn ($record) => AreaResource::getUrl('equipment', ['record' => $record], shouldGuessMissingParameters: true)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
