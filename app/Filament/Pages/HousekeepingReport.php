<?php

namespace App\Filament\Pages;

use App\Filament\Resources\HousekeepingPlans\HousekeepingPlanResource;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingPlanTask;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HousekeepingReport extends Page implements HasTable
{
    use HasPageShield, InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Report Pekerjaan';

    protected static ?string $title = 'Report Pekerjaan Housekeeping';

    protected static string|UnitEnum|null $navigationGroup = 'Housekeeping';

    protected static ?int $navigationSort = 5;

    public function getSubheading(): ?string
    {
        return 'Rekap seluruh item pekerjaan housekeeping dari semua plan harian dalam satu halaman.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->reportQuery())
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(1)
                    ->grow(false),
                TextColumn::make('plan.plan_date')
                    ->label('Tanggal')
                    ->date('d M Y'),
                TextColumn::make('plan.area.name')
                    ->label('Area')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('facility.name')
                    ->label('Fasilitas')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Pekerjaan')
                    ->searchable()
                    ->limit(45)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Done' => 'success',
                        'In Progress' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('photo_before')
                    ->label('Before')
                    ->badge()
                    ->getStateUsing(fn (HousekeepingPlanTask $record): string => count($record->photo_before ?? []).' foto')
                    ->color(fn (HousekeepingPlanTask $record): string => count($record->photo_before ?? []) > 0 ? 'success' : 'gray'),
                TextColumn::make('photo_after')
                    ->label('After')
                    ->badge()
                    ->getStateUsing(fn (HousekeepingPlanTask $record): string => count($record->photo_after ?? []).' foto')
                    ->color(fn (HousekeepingPlanTask $record): string => count($record->photo_after ?? []) > 0 ? 'success' : 'gray'),
                TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('completed_at')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('plan.area.supervisor.name')
                    ->label('Penanggung Jawab')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('plan.plan_number')
                    ->label('Plan')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('plan_period')
                    ->label('Periode Plan')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['from'] ?? null,
                            fn (Builder $query, $date) => $query->whereHas(
                                'plan',
                                fn (Builder $plan) => $plan->whereDate('plan_date', '>=', $date),
                            ),
                        )
                        ->when(
                            $data['until'] ?? null,
                            fn (Builder $query, $date) => $query->whereHas(
                                'plan',
                                fn (Builder $plan) => $plan->whereDate('plan_date', '<=', $date),
                            ),
                        )),
                SelectFilter::make('housekeeping_area_id')
                    ->label('Area')
                    ->options(fn (): array => HousekeepingArea::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query) => $query->whereHas(
                            'plan',
                            fn (Builder $plan) => $plan->where('housekeeping_area_id', $data['value']),
                        ),
                    )),
                SelectFilter::make('housekeeping_facility_id')
                    ->label('Fasilitas')
                    ->relationship('facility', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(HousekeepingPlanTask::STATUSES),
                Filter::make('no_after_photo')
                    ->label('Belum Ada Foto After')
                    ->toggle()
                    ->query(fn (Builder $query, array $data): Builder => ($data['isActive'] ?? false)
                        ? $query
                            ->where(fn (Builder $q) => $q->whereNull('photo_after')->orWhere('photo_after', '[]'))
                        : $query),
            ])
            ->recordActions([
                Action::make('view_plan')
                    ->label('Lihat Plan')
                    ->icon(Heroicon::OutlinedEye)
                    ->visible(fn (HousekeepingPlanTask $record): bool => $record->plan_id !== null)
                    ->url(fn (HousekeepingPlanTask $record): ?string => $record->plan_id !== null
                        ? HousekeepingPlanResource::getUrl('view', ['record' => $record->plan_id])
                        : null),
                Action::make('documentation')
                    ->label('Dokumentasi')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->modalHeading('Dokumentasi Pekerjaan')
                    ->modalWidth(Width::ThreeExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (HousekeepingPlanTask $record) => view('filament.pages.housekeeping-documentation', [
                        'record' => $record,
                    ])),
            ])
            ->emptyStateHeading('Belum ada pekerjaan tercatat')
            ->emptyStateDescription('Item pekerjaan muncul dari checklist Plan Harian housekeeping.');
    }

    public function content(Schema $schema): Schema
    {
        $stats = $this->reportStats();

        return $schema
            ->components([
                Grid::make(['default' => 1, 'md' => 2, 'xl' => 5])
                    ->schema([
                        Section::make('Total Pekerjaan')
                            ->compact()
                            ->description('Total item pekerjaan housekeeping')
                            ->schema([
                                Text::make((string) $stats['total'])
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold),
                            ]),
                        Section::make('Selesai')
                            ->compact()
                            ->description('Status Done')
                            ->schema([
                                Text::make((string) $stats['done'])
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('success'),
                            ]),
                        Section::make('Dikerjakan')
                            ->compact()
                            ->description('Status In Progress')
                            ->schema([
                                Text::make((string) $stats['in_progress'])
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('warning'),
                            ]),
                        Section::make('Pending')
                            ->compact()
                            ->description('Belum dimulai')
                            ->schema([
                                Text::make((string) $stats['pending'])
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('gray'),
                            ]),
                        Section::make('Progres')
                            ->compact()
                            ->description('Persentase pekerjaan selesai')
                            ->schema([
                                Text::make($stats['rate'].'%')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color($stats['rate'] >= 100 ? 'success' : ($stats['rate'] > 0 ? 'warning' : 'gray')),
                            ]),
                    ]),
                EmbeddedTable::make(),
            ]);
    }

    /**
     * Query dasar tabel report. Panel lapangan menimpanya agar hanya
     * menampilkan pekerjaan pada plan yang boleh dilihat user.
     */
    protected function reportQuery(): Builder
    {
        return HousekeepingPlanTask::query()
            ->with(['plan.area.company', 'plan.area.supervisor', 'facility']);
    }

    /**
     * @return array{total: int, done: int, in_progress: int, pending: int, rate: int}
     */
    protected function reportStats(): array
    {
        $query = $this->getFilteredTableQuery();

        if (! $query) {
            return ['total' => 0, 'done' => 0, 'in_progress' => 0, 'pending' => 0, 'rate' => 0];
        }

        $base = (clone $query)->reorder()->selectRaw('status, count(*) as aggregate')->groupBy('status');
        $counts = $base->pluck('aggregate', 'status');

        $done = (int) ($counts['Done'] ?? 0);
        $inProgress = (int) ($counts['In Progress'] ?? 0);
        $pending = (int) ($counts['Pending'] ?? 0);
        $total = $done + $inProgress + $pending;

        return [
            'total' => $total,
            'done' => $done,
            'in_progress' => $inProgress,
            'pending' => $pending,
            'rate' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
        ];
    }
}
