<?php

namespace App\Filament\Resources\HousekeepingAreas\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FacilitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'facilities';

    protected static ?string $title = 'Fasilitas / Objek Area';

    protected static ?string $modelLabel = 'facility';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Fasilitas')
                    ->required()
                    ->placeholder('e.g., Oil Catcher, Selokan, Taman'),
                TextInput::make('code')
                    ->label('Code')
                    ->nullable()
                    ->placeholder('e.g., OC-01, SLK-01'),
                Textarea::make('description')
                    ->label('Description')
                    ->nullable()
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Fasilitas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->toggleable(),
                BooleanColumn::make('is_active')
                    ->label('Active')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
