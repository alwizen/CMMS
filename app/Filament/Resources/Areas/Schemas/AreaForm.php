<?php

namespace App\Filament\Resources\Areas\Schemas;

use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Area Information')
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->options(Company::pluck('name', 'id'))
                            ->required()
                            ->placeholder('Select company'),
                        TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->placeholder('e.g., LOAD, UNLOAD'),
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->placeholder('e.g., Loading, Unloading'),
                        Textarea::make('description')
                            ->label('Description')
                            ->nullable()
                            ->placeholder('Enter area description...'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
