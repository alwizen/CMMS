<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(1)
                    ->grow(false),
                TextColumn::make('document_number')
                    ->label('Document Number')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($state): string => $state ?? '-'),
                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Document::TYPES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'certification' => 'success',
                        'pnid' => 'info',
                        'user_manual' => 'primary',
                        'drawing' => 'warning',
                        'procedure' => 'gray',
                        'inspection_report' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('equipment.tag_number')
                    ->label('Equipment')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('revision')
                    ->label('Revision')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('issuer')
                    ->label('Issuer')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('issue_date')
                    ->label('Issue Date')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expiry_date')
                    ->label('Expiry Date')
                    ->date()
                    ->placeholder('-'),
                TextColumn::make('expiry_status')
                    ->label('Expiry Status')
                    ->state(fn (Document $record): string => $record->expiryStatus())
                    ->formatStateUsing(fn (string $state): string => Document::EXPIRY_STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'valid' => 'success',
                        'expiring_soon' => 'warning',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->label('Document Type')
                    ->options(Document::TYPES),
                SelectFilter::make('equipment_id')
                    ->label('Equipment')
                    ->relationship('equipment', 'tag_number')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('expiry_status')
                    ->label('Expiry Status')
                    ->options(Document::EXPIRY_STATUSES)
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'expired' => $query
                                ->whereNotNull('expiry_date')
                                ->whereDate('expiry_date', '<', today()),
                            'expiring_soon' => $query
                                ->whereNotNull('expiry_date')
                                ->whereDate('expiry_date', '>=', today())
                                ->whereDate('expiry_date', '<=', today()->addDays(Document::EXPIRING_SOON_DAYS)),
                            'valid' => $query
                                ->whereNotNull('expiry_date')
                                ->whereDate('expiry_date', '>', today()->addDays(Document::EXPIRING_SOON_DAYS)),
                            'no_expiry' => $query->whereNull('expiry_date'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('open_file')
                    ->label('Open File')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Document $record): string => URL::temporarySignedRoute(
                        'documents.file',
                        now()->addMinutes(30),
                        ['document' => $record->id],
                    ))
                    ->openUrlInNewTab()
                    ->visible(fn (Document $record): bool => filled($record->file_path)),
                EditAction::make(),
            ]);
        // ->toolbarActions([
        //     BulkActionGroup::make([
        //         DeleteBulkAction::make(),
        //     ]),
        // ]);
    }
}
