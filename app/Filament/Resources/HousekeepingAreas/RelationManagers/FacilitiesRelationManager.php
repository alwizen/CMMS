<?php

namespace App\Filament\Resources\HousekeepingAreas\RelationManagers;

use App\Filament\Actions\CreateRelatedAction;
use App\Models\HousekeepingFacility;
use App\Models\HousekeepingTask;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
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
                    ->columnSpanFull()
                    ->required()
                    ->placeholder('e.g., Oil Catcher, Selokan, Taman'),
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
                TextColumn::make('name')
                    ->label('Nama Fasilitas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tasks_count')
                    ->label('Checklist')
                    ->counts('tasks')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('description')
                    ->label('Description')
                    ->placeholder('-')
                    ->limit(40)
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                $this->checklistAction(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    protected function checklistAction(): CreateRelatedAction
    {
        return CreateRelatedAction::make('checklist')
            ->relatedModel(HousekeepingTask::class)
            ->label('Checklist')
            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
            ->color('gray')
            ->relationship(fn (HousekeepingFacility $record) => $record->tasks())
            ->modalHeading(fn (HousekeepingFacility $record): string => 'Checklist — '.$record->name)
            ->modalWidth(Width::TwoExtraLarge)
            ->createAnother(false)
            ->modalContent(fn (HousekeepingFacility $record) => view('filament.relation-managers.facility-checklist', [
                'record' => $record->load('tasks'),
            ]))
            ->form(fn (): array => [
                TextInput::make('name')
                    ->label('Nama Checklist')
                    ->required()
                    ->placeholder('e.g., Pemotongan Rumput, Pembersihan Selokan'),
                Textarea::make('description')
                    ->label('Description')
                    ->nullable()
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ])
            ->visible(fn (): bool => auth()->user()?->can('create', HousekeepingTask::class) ?? false)
            ->successNotificationTitle('Checklist ditambahkan');
    }
}
