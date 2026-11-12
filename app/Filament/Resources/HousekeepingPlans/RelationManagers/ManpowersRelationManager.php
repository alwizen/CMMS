<?php

namespace App\Filament\Resources\HousekeepingPlans\RelationManagers;

use App\Models\HousekeepingManpower;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ManpowersRelationManager extends RelationManager
{
    protected static string $relationship = 'manpowers';

    protected static ?string $title = 'Manpower (Petugas)';

    protected static ?string $modelLabel = 'manpower';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('housekeeping_manpower_id')
                    ->label('Manpower')
                    ->options(fn (): array => HousekeepingManpower::query()
                        ->where('is_active', true)
                        ->where('housekeeping_area_id', $this->getOwnerRecord()->housekeeping_area_id)
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (HousekeepingManpower $manpower): array => [
                            $manpower->id => $manpower->name
                                .($manpower->employee_number ? ' ('.$manpower->employee_number.')' : ''),
                        ])
                        ->all())
                    ->searchable()
                    ->required(),
                Textarea::make('note')
                    ->label('Note')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee_number')
                    ->label('NIK')
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('Phone')
                    ->toggleable(),
                TextColumn::make('pivot.note')
                    ->label('Note')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelect(fn (Select $select) => $select
                        ->options(fn (): array => HousekeepingManpower::query()
                            ->where('is_active', true)
                            ->where('housekeeping_area_id', $this->getOwnerRecord()->housekeeping_area_id)
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (HousekeepingManpower $manpower): array => [
                                $manpower->id => $manpower->name
                                    .($manpower->employee_number ? ' ('.$manpower->employee_number.')' : ''),
                            ])
                            ->all())
                        ->searchable()),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
