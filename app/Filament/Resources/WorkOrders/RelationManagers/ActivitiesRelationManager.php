<?php

namespace App\Filament\Resources\WorkOrders\RelationManagers;

use App\Models\Activity;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'workOrderActivities';

    protected static ?string $title = 'Checklist & Hasil Eksekusi';

    protected static ?string $modelLabel = 'activity result';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Pekerjaan')
                    ->schema([
                        Select::make('activity_id')
                            ->label('Activity')
                            ->options(fn (): array => Activity::query()
                                ->with('equipmentType')
                                ->where('status', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Activity $activity): array => [
                                    $activity->id => $activity->name.' ('.$activity->equipmentType?->name.')',
                                ])
                                ->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('reference')
                            ->label('Reference')
                            ->nullable(),
                        TextInput::make('unit')
                            ->label('Unit')
                            ->nullable(),
                    ])
                    ->columns(2),
                Section::make('Hasil Pengerjaan')
                    ->schema([
                        TextInput::make('pre_inspection')
                            ->label('Pre Inspection')
                            ->nullable(),
                        TextInput::make('final_result')
                            ->label('Final Result')
                            ->nullable(),
                        TextInput::make('follow_up')
                            ->label('Follow Up')
                            ->nullable(),
                        Toggle::make('executed')
                            ->label('Executed')
                            ->default(false),
                        Textarea::make('note')
                            ->label('Note')
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('activity.name')
                    ->label('Activity')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reference')
                    ->label('Reference')
                    ->toggleable(),
                TextColumn::make('pre_inspection')
                    ->label('Pre Inspection')
                    ->toggleable(),
                TextColumn::make('final_result')
                    ->label('Final Result')
                    ->toggleable(),
                TextColumn::make('unit')
                    ->label('Unit')
                    ->toggleable(),
                IconColumn::make('executed')
                    ->label('Executed')
                    ->boolean()
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
