<?php

namespace App\Filament\Resources\MaintenancePlans\RelationManagers;

use App\Models\Activity;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'planActivities';

    protected static ?string $title = 'Checklist Activities';

    protected static ?string $modelLabel = 'activity';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TextInput::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->default(fn (ActivitiesRelationManager $livewire): int => $livewire->nextSortOrder()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('activity.name')
                    ->label('Activity')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('activity.type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('activity.maintenance_classification')
                    ->label('Classification')
                    ->badge()
                    ->sortable(),
                TextColumn::make('activity.interval')
                    ->label('Interval')
                    ->sortable(),
                TextColumn::make('activity.answer_type')
                    ->label('Answer')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
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

    public function nextSortOrder(): int
    {
        return (int) $this->getOwnerRecord()->planActivities()->max('sort_order') + 1;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['sort_order'] ?? null)) {
            $data['sort_order'] = $this->nextSortOrder();
        }

        return $data;
    }
}
