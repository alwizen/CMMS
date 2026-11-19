<?php

namespace App\Filament\Resources\HousekeepingTasks\Tables;

use App\Models\HousekeepingArea;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HousekeepingTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(1)
                    ->grow(false),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('facility.name')
                    ->label('Fasilitas')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('facility.area.name')
                    ->label('Area')
                    ->placeholder('-')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('area_id')
                    ->label('Area')
                    ->options(fn (): array => HousekeepingArea::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query) => $query->whereHas(
                            'facility',
                            fn (Builder $facility) => $facility->where('housekeeping_area_id', $data['value']),
                        ),
                    )),
                SelectFilter::make('housekeeping_facility_id')
                    ->label('Fasilitas')
                    ->relationship('facility', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Status'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
