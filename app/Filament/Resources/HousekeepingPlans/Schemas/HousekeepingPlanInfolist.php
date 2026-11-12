<?php

namespace App\Filament\Resources\HousekeepingPlans\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HousekeepingPlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Plan')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('plan_number')
                            ->label('Plan Number')
                            ->placeholder('-'),
                        TextEntry::make('plan_date')
                            ->label('Tanggal')
                            ->date('d M Y'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Completed' => 'success',
                                'In Progress' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('area.name')
                            ->label('Area')
                            ->placeholder('-'),
                        TextEntry::make('area.company.name')
                            ->label('Company')
                            ->placeholder('-'),
                        TextEntry::make('area.supervisor.name')
                            ->label('Penanggung Jawab')
                            ->placeholder('-'),
                        TextEntry::make('area.area_size')
                            ->label('Luas Area')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' m²')
                            ->placeholder('-'),
                        TextEntry::make('createdBy.name')
                            ->label('Dibuat Oleh')
                            ->placeholder('-'),
                        TextEntry::make('note')
                            ->label('Catatan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Checklist Pekerjaan')
                    ->icon('heroicon-o-check-circle')
                    ->columnSpanFull()
                    ->description('Item pekerjaan beserta status dan foto before/after.')
                    ->schema([
                        RepeatableEntry::make('tasks')
                            ->columnSpanFull()
                            ->hiddenLabel()
                            ->placeholder('Belum ada item checklist.')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Pekerjaan')
                                    ->weight('bold'),
                                TextEntry::make('facility.name')
                                    ->label('Fasilitas')
                                    ->placeholder('-'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Done' => 'success',
                                        'In Progress' => 'warning',
                                        default => 'gray',
                                    }),
                                TextEntry::make('started_at')
                                    ->label('Mulai')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),
                                TextEntry::make('completed_at')
                                    ->label('Selesai')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),
                                TextEntry::make('note')
                                    ->label('Catatan')
                                    ->placeholder('-'),
                                ImageEntry::make('photo_before')
                                    ->label('Foto Before')
                                    ->disk('public')
                                    ->size(120)
                                    ->placeholder('-'),
                                ImageEntry::make('photo_after')
                                    ->label('Foto After')
                                    ->disk('public')
                                    ->size(120)
                                    ->placeholder('-'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}
