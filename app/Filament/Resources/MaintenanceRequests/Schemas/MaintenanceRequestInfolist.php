<?php

namespace App\Filament\Resources\MaintenanceRequests\Schemas;

use App\Models\MaintenanceRequest;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MaintenanceRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Utama')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        TextEntry::make('request_number')
                            ->label('Request Number'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Open' => 'info',
                                'Assigned' => 'primary',
                                'In Progress' => 'warning',
                                'Completed' => 'success',
                                'Approved' => 'success',
                                'Rejected' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('operation_status')
                            ->label('Operation Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Running' => 'success',
                                'Stopped' => 'danger',
                                'Standby' => 'warning',
                                'Faulty' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('description')
                            ->label('Deskripsi'),
                        TextEntry::make('damage_date')
                            ->label('Tanggal Kerusakan')
                            ->date('d M Y'),
                        TextEntry::make('damage_time')
                            ->label('Waktu')
                            ->dateTime('H:i')
                            ->placeholder('-'),
                    ])->columns(2),

                Section::make('Kondisi & Dampak')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->schema([
                        TextEntry::make('equipment_condition')
                            ->label('Kondisi Equipment')
                            ->placeholder('-'),
                        TextEntry::make('impact')
                            ->label('Dampak')
                            ->placeholder('-'),
                        TextEntry::make('early_action')
                            ->label('Tindakan Awal')
                            ->placeholder('-'),
                    ])->columns(3),

                Section::make('Equipment')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->schema([
                        TextEntry::make('equipment.name')
                            ->label('Nama'),
                        TextEntry::make('equipment.tag_number')
                            ->label('Tag Number'),
                        TextEntry::make('equipment.equipmentType.name')
                            ->label('Tipe')
                            ->placeholder('-'),
                        TextEntry::make('equipment.area.name')
                            ->label('Area')
                            ->placeholder('-'),
                    ])->columns(2),

                Section::make('Pelapor')
                    ->icon(Heroicon::OutlinedUser)
                    ->schema([
                        TextEntry::make('reportedBy.name')
                            ->label('Nama'),
                        TextEntry::make('created_at')
                            ->label('Tanggal Laporan')
                            ->dateTime('d M Y H:i'),
                    ])->columns(2),

                Section::make('Foto')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->schema([
                        ImageEntry::make('photo')
                            ->disk('public')
                            ->size(400)
                            ->placeholder('Tidak ada foto'),
                    ])
                    ->visible(fn (MaintenanceRequest $record): bool => !empty($record->photo)),

                Section::make('Approval')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->schema([
                        TextEntry::make('approval_notes')
                            ->label('Catatan')
                            ->placeholder('-'),
                        TextEntry::make('approvedBy.name')
                            ->label('Disetujui Oleh')
                            ->placeholder('-'),
                        TextEntry::make('approved_at')
                            ->label('Tanggal Approval')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->visible(fn (MaintenanceRequest $record): bool => in_array($record->status, ['Approved', 'Rejected'])),
            ]);
    }
}
