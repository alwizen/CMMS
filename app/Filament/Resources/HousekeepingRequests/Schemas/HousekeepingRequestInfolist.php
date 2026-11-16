<?php

namespace App\Filament\Resources\HousekeepingRequests\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HousekeepingRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Request')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('request_number')
                            ->label('Request Number')
                            ->placeholder('-'),
                        TextEntry::make('request_date')
                            ->label('Tanggal')
                            ->date('d M Y'),
                        TextEntry::make('request_time')
                            ->label('Jam')
                            ->time('H:i')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Approved' => 'success',
                                'Rejected' => 'danger',
                                default => 'info',
                            }),
                        TextEntry::make('priority')
                            ->label('Prioritas')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'High' => 'danger',
                                'Low' => 'gray',
                                default => 'warning',
                            }),
                        TextEntry::make('area.name')
                            ->label('Area')
                            ->placeholder('-'),
                        TextEntry::make('facility.name')
                            ->label('Fasilitas')
                            ->placeholder('Seluruh area'),
                        TextEntry::make('requestedBy.name')
                            ->label('Diminta Oleh')
                            ->placeholder('-'),
                        TextEntry::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull()
                            ->placeholder('-'),
                        ImageEntry::make('photo')
                            ->label('Foto')
                            ->disk('public')
                            ->size(160)
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                Section::make('Approval')
                    ->icon('heroicon-o-check-badge')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('approvedBy.name')
                            ->label('Disetujui Oleh')
                            ->placeholder('-'),
                        TextEntry::make('approved_at')
                            ->label('Tanggal Approval')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('plan.plan_number')
                            ->label('Plan Harian')
                            ->placeholder('-'),
                        TextEntry::make('approval_notes')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->visible(fn ($record): bool => $record?->status !== 'Open'),
            ]);
    }
}
