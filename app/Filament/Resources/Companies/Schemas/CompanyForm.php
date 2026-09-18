<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('e.g., FT-TGL'),
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->placeholder('e.g., FT Tegal'),
                Textarea::make('description')
                    ->label('Description')
                    ->nullable()
                    ->placeholder('Enter company description...'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
