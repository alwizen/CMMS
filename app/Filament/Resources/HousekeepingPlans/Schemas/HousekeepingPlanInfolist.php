<?php

namespace App\Filament\Resources\HousekeepingPlans\Schemas;

use App\Models\HousekeepingPlan;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
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
                        TextEntry::make('progress_percent')
                            ->label('Progress')
                            ->badge()
                            ->formatStateUsing(fn (int $state): string => $state.'%')
                            ->color(fn (int $state): string => match (true) {
                                $state >= 100 => 'success',
                                $state > 0 => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('area.sla_days')
                            ->label('SLA Pengerjaan')
                            ->badge()
                            ->formatStateUsing(fn (?int $state): string => ($state ?? 1).' hari')
                            ->color('gray'),
                        TextEntry::make('due_date')
                            ->label('Batas Waktu')
                            ->date('d M Y')
                            ->placeholder('-'),
                        TextEntry::make('sla_status')
                            ->label('Status SLA')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Completed' => 'success',
                                'On Track' => 'info',
                                'At Risk' => 'warning',
                                'Overdue' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('approval_status')
                            ->label('Approval SPV GOS')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => HousekeepingPlan::APPROVAL_STATUSES[$state] ?? $state)
                            ->color(fn (string $state): string => match ($state) {
                                'Approved' => 'success',
                                'Rejected' => 'danger',
                                default => 'warning',
                            }),
                        TextEntry::make('approvedBy.name')
                            ->label('Disetujui Oleh')
                            ->placeholder('-'),
                        TextEntry::make('approved_at')
                            ->label('Waktu Approval')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('approval_notes')
                            ->label('Catatan Approval')
                            ->placeholder('-')
                            ->columnSpanFull(),
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
                    ->description('Item pekerjaan beserta status pengerjaan.')
                    ->schema([
                        RepeatableEntry::make('tasks')
                            ->columnSpanFull()
                            ->hiddenLabel()
                            ->placeholder('Belum ada item checklist.')
                            ->table([
                                TableColumn::make('Daftar Cheklist'),
                                TableColumn::make('Fasilitas'),
                                TableColumn::make('Status'),
                                TableColumn::make('Foto Before'),
                                TableColumn::make('Foto After'),
                                TableColumn::make('Mulai'),
                                TableColumn::make('Selesai'),
                                TableColumn::make('Catatan'),
                            ])
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Pekerjaan')
                                    ->wrap()
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
                                ImageEntry::make('photo_before')
                                    ->label('Before')
                                    ->disk('public')
                                    ->size(60)
                                    ->placeholder('-'),
                                ImageEntry::make('photo_after')
                                    ->label('After')
                                    ->disk('public')
                                    ->size(60)
                                    ->placeholder('-'),
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
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
}
