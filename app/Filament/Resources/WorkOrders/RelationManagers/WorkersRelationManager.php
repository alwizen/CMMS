<?php

namespace App\Filament\Resources\WorkOrders\RelationManagers;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkersRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrderWorkers';

    protected static ?string $title = 'Teknisi Pelaksana';

    protected static ?string $modelLabel = 'worker';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Teknisi')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->required(),
                TextInput::make('role')
                    ->label('Role')
                    ->placeholder('e.g., Teknisi, Supervisor')
                    ->nullable(),
                DateTimePicker::make('started_at')
                    ->label('Started At')
                    ->nullable(),
                DateTimePicker::make('finished_at')
                    ->label('Finished At')
                    ->nullable(),
                Textarea::make('note')
                    ->label('Note')
                    ->nullable()
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Teknisi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label('Started At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label('Finished At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('note')
                    ->label('Note')
                    ->limit(40)
                    ->toggleable(),
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
