<?php

namespace App\Filament\Resources\Findings\Tables;

use App\Filament\Resources\WorkOrders\Actions\SubmitFindingRequestAction;
use App\Models\WorkOrderFinding;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FindingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('finding_number')
                    ->label('No. Temuan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('equipment.tag_number')
                    ->label('Tag Number')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('activity_name')
                    ->label('Aktivitas')
                    ->state(fn (WorkOrderFinding $record): string => $record->displayName())
                    ->wrap(),
                TextColumn::make('answer_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => WorkOrderFinding::ANSWER_TYPES[$state] ?? $state)
                    ->color(fn (string $state): string => $state === 'quantitative' ? 'info' : 'gray'),
                TextColumn::make('result')
                    ->label('Hasil')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Poor' => 'warning',
                        'Not Working' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Critical' => 'danger',
                        'High' => 'warning',
                        'Medium' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => WorkOrderFinding::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'Open' => 'warning',
                        'Requested' => 'info',
                        'Resolved' => 'success',
                        'Dismissed' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('workOrder.work_order_number')
                    ->label('No. WO')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('maintenanceRequest.request_number')
                    ->label('Request Perbaikan')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(WorkOrderFinding::STATUSES),
                SelectFilter::make('severity')
                    ->label('Severity')
                    ->options(WorkOrderFinding::SEVERITIES),
                SelectFilter::make('answer_type')
                    ->label('Tipe Jawaban')
                    ->options(WorkOrderFinding::ANSWER_TYPES),
                Filter::make('created_period')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari Tanggal'),
                        DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
                SubmitFindingRequestAction::make(),
            ])
            ->emptyStateHeading('Belum ada temuan')
            ->emptyStateDescription('Temuan terbentuk otomatis dari hasil checklist work order yang bermasalah (Poor/Not Working atau di luar batas).');
    }
}
