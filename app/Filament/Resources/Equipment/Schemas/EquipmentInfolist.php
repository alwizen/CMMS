<?php

namespace App\Filament\Resources\Equipment\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EquipmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Information')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('tag_number')
                            ->label('Tag Number'),
                        TextEntry::make('pm_tag')
                            ->label('PM Tag')
                            ->placeholder('-'),
                        TextEntry::make('equipmentType.name')
                            ->label('Equipment Type')
                            ->placeholder('-'),
                        TextEntry::make('area.name')
                            ->label('Area')
                            ->placeholder('-'),
                        TextEntry::make('area.company.name')
                            ->label('Company')
                            ->placeholder('-'),
                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Technical Details')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('manufacturer')
                            ->label('Manufacturer')
                            ->placeholder('-'),
                        TextEntry::make('model')
                            ->label('Model')
                            ->placeholder('-'),
                        TextEntry::make('serial_number')
                            ->label('Serial Number')
                            ->placeholder('-'),
                        TextEntry::make('installation_date')
                            ->label('Installation Date')
                            ->date('d M Y')
                            ->placeholder('-'),
                        TextEntry::make('operational_unit')
                            ->label('Operational Unit')
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                Section::make('Status & Media')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'inactive' => 'gray',
                                'retired' => 'danger',
                            }),
                        TextEntry::make('criticality')
                            ->label('Criticality')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'low' => 'gray',
                                'medium' => 'warning',
                                'high' => 'danger',
                                'critical' => 'danger',
                            }),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        ImageEntry::make('photo')
                            ->label('Photo')
                            ->disk('local')
                            ->size(320)
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }
}
