<?php

namespace App\Filament\App\Resources\HousekeepingRequests\Tables;

use App\Filament\App\Resources\HousekeepingRequests\HousekeepingRequestResource;
use App\Models\HousekeepingRequest;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HousekeepingRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('request_date', 'desc')
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(1)
                    ->grow(false),
                TextColumn::make('request_number')
                    ->label('Request Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('request_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('area.name')
                    ->label('Area')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('facility.name')
                    ->label('Fasilitas')
                    ->placeholder('Seluruh area')
                    ->toggleable(),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'High' => 'danger',
                        'Low' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        default => 'info',
                    })
                    ->sortable(),
                TextColumn::make('plan.plan_number')
                    ->label('Plan Harian')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(HousekeepingRequest::STATUSES),
                SelectFilter::make('priority')
                    ->options(HousekeepingRequest::PRIORITIES),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (HousekeepingRequest $record): bool => HousekeepingRequestResource::canEdit($record)),
            ]);
    }
}
