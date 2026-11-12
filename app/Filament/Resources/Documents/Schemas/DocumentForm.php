<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Models\Document;
use App\Models\Equipment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Information')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('e.g., Pump Calibration Certificate'),
                        Select::make('document_type')
                            ->label('Document Type')
                            ->options(Document::TYPES)
                            ->required()
                            ->live()
                            ->placeholder('Select document type'),
                        Select::make('equipment_id')
                            ->label('Equipment')
                            ->options(Equipment::pluck('tag_number', 'id'))
                            ->searchable()
                            ->nullable()
                            ->placeholder('Select equipment (optional)'),
                        TextInput::make('revision')
                            ->label('Revision')
                            ->nullable()
                            ->placeholder('e.g., Rev. 02'),
                        TextInput::make('issuer')
                            ->label('Issuer')
                            ->nullable()
                            ->placeholder('e.g., PT Surveyor Indonesia'),
                    ])
                    ->columns(2),

                Section::make('Validity')
                    ->schema([
                        DatePicker::make('issue_date')
                            ->label('Issue Date')
                            ->nullable(),
                        DatePicker::make('expiry_date')
                            ->label('Expiry Date')
                            ->nullable()
                            ->required(fn (Get $get): bool => in_array($get('document_type'), Document::EXPIRABLE_TYPES, true))
                            ->helperText('Required for certification and inspection report documents.'),
                    ])
                    ->columns(2),

                Section::make('File & Notes')
                    ->schema([
                        FileUpload::make('file_path')
                            ->label('Document File')
                            ->disk('local')
                            ->directory('documents')
                            ->visibility('private')
                            ->maxSize(20480)
                            ->openable()
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Description')
                            ->nullable()
                            ->placeholder('Enter document description or notes...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
