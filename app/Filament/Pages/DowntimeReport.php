<?php

namespace App\Filament\Pages;

use App\Filament\Resources\MaintenanceRequests\MaintenanceRequestResource;
use App\Models\Area;
use App\Models\MaintenanceRequest;
use App\Models\WorkOrder;
use App\Support\Downtime;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DowntimeReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Downtime';

    protected static ?string $title = 'Unplanned Downtime';

    protected static string|UnitEnum|null $navigationGroup = 'Riwayat & Monitoring';

    protected static ?int $navigationSort = 3;

    public function getSubheading(): ?string
    {
        return 'Downtime tak terencana dihitung otomatis dari Maintenance Request sampai Work Order selesai.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => MaintenanceRequest::query()
                ->with(['equipment.area', 'reportedBy', 'workOrders'])
                ->where('status', '!=', MaintenanceRequest::STATUSES['Rejected']))
            ->defaultSort('damage_date', 'desc')
            ->columns([
                TextColumn::make('request_number')
                    ->label('Request Number')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('equipment.tag_number')
                    ->label('Tag Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('equipment.area.name')
                    ->label('Area')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('description')
                    ->label('Masalah')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($state): ?string => $state),
                TextColumn::make('downtime_start')
                    ->label('Mulai')
                    ->state(fn (MaintenanceRequest $record) => $record->downtimeStart())
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
                TextColumn::make('downtime_end')
                    ->label('Selesai')
                    ->state(fn (MaintenanceRequest $record) => $record->downtimeEnd())
                    ->dateTime('d M Y H:i')
                    ->placeholder('Masih berlangsung'),
                TextColumn::make('downtime_minutes')
                    ->label('Durasi')
                    ->state(fn (MaintenanceRequest $record): ?int => $record->downtimeMinutes())
                    ->formatStateUsing(fn ($state): string => Downtime::formatMinutes($state === null ? null : (int) $state))
                    ->placeholder('-')
                    ->weight(FontWeight::Bold),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        MaintenanceRequest::STATUSES['Open'] => 'info',
                        MaintenanceRequest::STATUSES['Approved'] => 'success',
                        MaintenanceRequest::STATUSES['Assigned'] => 'primary',
                        MaintenanceRequest::STATUSES['In Progress'] => 'warning',
                        MaintenanceRequest::STATUSES['Completed'] => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Filter::make('damage_period')
                    ->label('Periode Kejadian')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['from'] ?? null,
                            fn (Builder $query, $date) => $query->whereDate('damage_date', '>=', $date),
                        )
                        ->when(
                            $data['until'] ?? null,
                            fn (Builder $query, $date) => $query->whereDate('damage_date', '<=', $date),
                        )),
                SelectFilter::make('equipment_id')
                    ->label('Equipment')
                    ->relationship('equipment', 'tag_number')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('area_id')
                    ->label('Area')
                    ->options(fn (): array => Area::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query) => $query->whereHas(
                            'equipment',
                            fn (Builder $equipmentQuery) => $equipmentQuery->where('area_id', $data['value']),
                        ),
                    )),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(MaintenanceRequest::STATUSES),
                Filter::make('ongoing')
                    ->label('Masih Berlangsung')
                    ->toggle()
                    ->query(fn (Builder $query, array $data): Builder => ($data['isActive'] ?? false)
                        ? $query
                            ->whereNotIn('status', [
                                MaintenanceRequest::STATUSES['Completed'],
                                MaintenanceRequest::STATUSES['Rejected'],
                            ])
                            ->whereDoesntHave(
                                'workOrders',
                                fn (Builder $workOrderQuery) => $workOrderQuery
                                    ->where('status', '!=', WorkOrder::STATUSES['Cancelled'])
                                    ->whereNotNull('finish_at'),
                            )
                        : $query),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Lihat Request')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (MaintenanceRequest $record): string => MaintenanceRequestResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('Belum ada downtime tercatat')
            ->emptyStateDescription('Downtime muncul otomatis dari maintenance request yang ditindaklanjuti work order.');
    }

    public function content(Schema $schema): Schema
    {
        $stats = $this->downtimeStats();

        return $schema
            ->components([
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 4])
                    ->schema([
                        Section::make('Total Downtime')
                            ->compact()
                            ->description('Akumulasi seluruh kejadian')
                            ->schema([
                                Text::make($stats['total'])
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('danger'),
                            ]),
                        Section::make('Jumlah Kejadian')
                            ->compact()
                            ->description('Request yang sudah terhitung')
                            ->schema([
                                Text::make((string) $stats['events'])
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold),
                            ]),
                        Section::make('Rata-rata')
                            ->compact()
                            ->description('Per kejadian')
                            ->schema([
                                Text::make($stats['average'])
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold),
                            ]),
                        Section::make('Masih Berlangsung')
                            ->compact()
                            ->description('Belum ada work order selesai')
                            ->schema([
                                Text::make((string) $stats['ongoing'])
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color($stats['ongoing'] > 0 ? 'warning' : 'success'),
                            ]),
                    ]),
                EmbeddedTable::make(),
            ]);
    }

    /**
     * @return array{total: string, events: int, average: string, ongoing: int}
     */
    protected function downtimeStats(): array
    {
        $query = $this->getFilteredTableQuery();

        $requests = $query
            ? $query->with(['workOrders', 'equipment'])->get()
            : collect();

        $totalMinutes = Downtime::totalMinutes($requests);
        $events = Downtime::eventCount($requests);
        $ongoing = $requests->filter(fn (MaintenanceRequest $record): bool => $record->downtimeOngoing())->count();

        return [
            'total' => Downtime::formatHours($totalMinutes),
            'events' => $events,
            'average' => Downtime::formatMinutes($events > 0 ? intdiv($totalMinutes, $events) : null),
            'ongoing' => $ongoing,
        ];
    }
}
