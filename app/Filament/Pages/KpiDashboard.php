<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Support\Kpi\KpiPeriod;
use App\Support\Kpi\KpiRegistry;
use App\Support\Kpi\KpiResult;
use App\Support\Kpi\KpiService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use UnitEnum;

class KpiDashboard extends Page implements HasTable
{
    use HasPageShield, InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'KPI Dashboard';

    protected static ?string $title = 'KPI Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'KPI';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.kpi-dashboard';

    /**
     * Filter yang sudah diterapkan (dipakai perhitungan).
     *
     * @var array<string, mixed>
     */
    public array $filters = [];

    /**
     * Nilai filter pada form (belum diterapkan sampai tombol Terapkan ditekan).
     *
     * @var array<string, mixed>
     */
    public array $draft = [];

    public function mount(): void
    {
        $defaults = [
            'source' => KpiRegistry::SOURCE_MAINTENANCE,
            'fungsi' => null,
            'user_id' => null,
            'period' => 'this_month',
            'date_from' => null,
            'date_to' => null,
        ];

        $this->filters = $defaults;
        $this->draft = $defaults;
    }

    /**
     * Terapkan filter dari form ke perhitungan.
     */
    public function applyFilters(): void
    {
        $this->filters = [
            'source' => $this->draft['source'] ?? KpiRegistry::SOURCE_MAINTENANCE,
            'fungsi' => $this->draft['fungsi'] ?? null,
            'user_id' => $this->draft['user_id'] ?? null,
            'period' => $this->draft['period'] ?? 'this_month',
            'date_from' => $this->draft['date_from'] ?? null,
            'date_to' => $this->draft['date_to'] ?? null,
        ];

        $this->resetTable();
    }

    /**
     * Kembalikan filter ke default (belum diterapkan sampai tombol Terapkan ditekan).
     */
    public function resetFilters(): void
    {
        $this->draft = [
            'source' => KpiRegistry::SOURCE_MAINTENANCE,
            'fungsi' => null,
            'user_id' => null,
            'period' => 'this_month',
            'date_from' => null,
            'date_to' => null,
        ];
    }

    public function fungsi(): ?string
    {
        $fungsi = $this->filters['fungsi'] ?? null;

        return filled($fungsi) ? (string) $fungsi : null;
    }

    public function userId(): ?int
    {
        $userId = $this->filters['user_id'] ?? null;

        return filled($userId) ? (int) $userId : null;
    }

    public function getSubheading(): ?string
    {
        return 'Penilaian kinerja maintenance per teknisi/koordinator dan housekeeping per area, berbasis data yang tercatat di sistem.';
    }

    public function filterSchema(Schema $schema): Schema
    {
        return $schema
            ->statePath('draft')
            ->components([
                Select::make('source')
                    ->label('Sumber Penilaian')
                    ->options([
                        KpiRegistry::SOURCE_MAINTENANCE => 'Maintenance',
                        KpiRegistry::SOURCE_HOUSEKEEPING_INDOOR => 'Housekeeping Indoor',
                        KpiRegistry::SOURCE_HOUSEKEEPING_OUTDOOR => 'Housekeeping Outdoor',
                    ])
                    ->live()
                    ->required(),
                Select::make('fungsi')
                    ->label('Fungsi')
                    ->options(User::FUNGSI)
                    ->live()
                    ->searchable()
                    ->visible(fn ($get): bool => ($get('source') ?? KpiRegistry::SOURCE_MAINTENANCE) === KpiRegistry::SOURCE_MAINTENANCE)
                    ->afterStateUpdated(fn (Set $set) => $set('user_id', null))
                    ->placeholder('Semua fungsi'),
                Select::make('user_id')
                    ->label('Nama')
                    ->options(fn (Get $get): array => User::query()
                        ->where('status', true)
                        ->whereNotNull('fungsi')
                        ->when(filled($get('fungsi')), fn ($query) => $query->where('fungsi', $get('fungsi')))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->live()
                    ->searchable()
                    ->visible(fn ($get): bool => ($get('source') ?? KpiRegistry::SOURCE_MAINTENANCE) === KpiRegistry::SOURCE_MAINTENANCE)
                    ->placeholder('Semua teknisi'),
                Select::make('period')
                    ->label('Periode')
                    ->options(KpiPeriod::presets())
                    ->live()
                    ->required(),
                DatePicker::make('date_from')
                    ->label('Dari Tanggal')
                    ->visible(fn ($get): bool => $get('period') === 'custom')
                    ->live(),
                DatePicker::make('date_to')
                    ->label('Sampai Tanggal')
                    ->visible(fn ($get): bool => $get('period') === 'custom')
                    ->live(),
            ])
            ->columns(6);
    }

    public function period(): KpiPeriod
    {
        $filters = $this->filters;

        return KpiPeriod::fromPreset(
            (string) ($filters['period'] ?? 'this_month'),
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
        );
    }

    /**
     * @return Collection<int, KpiResult>
     */
    public function results(): Collection
    {
        return app(KpiService::class)->results(
            (string) ($this->filters['source'] ?? KpiRegistry::SOURCE_MAINTENANCE),
            $this->period(),
            $this->fungsi(),
            $this->userId(),
        );
    }

    public function summary(): array
    {
        return app(KpiService::class)->summary(
            (string) ($this->filters['source'] ?? KpiRegistry::SOURCE_MAINTENANCE),
            $this->period(),
            $this->fungsi(),
            $this->userId(),
        );
    }

    public function table(Table $table): Table
    {
        $source = (string) ($this->filters['source'] ?? KpiRegistry::SOURCE_MAINTENANCE);
        $period = $this->period();
        $results = app(KpiService::class)->results($source, $period, $this->fungsi(), $this->userId());

        return $table
            ->records(fn (): array => $results
                ->map(fn (KpiResult $result): array => [
                    'id' => $result->objectId,
                    'objectName' => $result->objectName,
                    'score' => $result->score,
                    'grade' => $result->grade(),
                    'grade_color' => $result->gradeColor(),
                    'status' => $result->status(),
                    'status_color' => $result->statusColor(),
                    'metrics' => $result->metrics,
                    'fungsi' => $result->context['fungsi'] ?? null,
                    'total_wo' => $result->context['total_wo'] ?? null,
                    'completed_wo' => $result->context['completed_wo'] ?? null,
                    'open_wo' => $result->context['open_wo'] ?? null,
                    'expired_wo' => $result->context['expired_wo'] ?? null,
                ])
                ->values()
                ->all())
            ->columns([
                TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->alignCenter()
                    ->width(1)
                    ->grow(false),
                TextColumn::make('objectName')
                    ->label(KpiRegistry::isHousekeeping($source) ? 'Area' : 'Nama')
                    ->searchable()
                    ->weight(FontWeight::Medium),
                ...$this->contextColumns($source),
                ...$this->metricColumns($source),
                TextColumn::make('score')
                    ->label('Skor')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (array $record): string => $record['grade_color'])
                    ->sortable(),
                TextColumn::make('grade')
                    ->label('Grade')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (array $record): string => $record['grade_color']),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (array $record): string => $record['status_color']),
            ])
            ->paginated([10, 25, 50])
            ->defaultSort('score', 'desc')
            ->emptyStateHeading('Belum ada data KPI')
            ->emptyStateDescription('Pilih periode lain, atau pastikan sudah ada WO/plan yang tercatat pada periode tersebut.');
    }

    /**
     * Kolom ringkasan jumlah WO (khusus maintenance).
     *
     * @return array<int, TextColumn>
     */
    protected function contextColumns(string $source): array
    {
        if ($source !== KpiRegistry::SOURCE_MAINTENANCE) {
            return [];
        }

        return [
            TextColumn::make('fungsi')
                ->label('Fungsi')
                ->badge()
                ->placeholder('-')
                ->toggleable(),
            TextColumn::make('total_wo')
                ->label('Total WO')
                ->alignCenter()
                ->badge()
                ->color('gray'),
            TextColumn::make('completed_wo')
                ->label('Selesai')
                ->alignCenter()
                ->badge()
                ->color('success'),
            TextColumn::make('open_wo')
                ->label('Belum')
                ->alignCenter()
                ->badge()
                ->color(fn (array $record): string => ($record['open_wo'] ?? 0) > 0 ? 'warning' : 'gray'),
            TextColumn::make('expired_wo')
                ->label('Expired')
                ->alignCenter()
                ->badge()
                ->color(fn (array $record): string => ($record['expired_wo'] ?? 0) > 0 ? 'danger' : 'gray'),
        ];
    }

    /**
     * @return array<int, TextColumn>
     */
    protected function metricColumns(string $source): array
    {
        $columns = [];

        foreach (KpiRegistry::metrics($source) as $key => $metric) {
            $columns[] = TextColumn::make('metrics.'.$key.'.display')
                ->label($metric['label'])
                ->alignCenter()
                ->placeholder('-')
                ->tooltip(fn (array $record): string => $metric['label'].' — target '.$metric['target'].' '.$metric['unit']);
        }

        return $columns;
    }

    public function content(Schema $schema): Schema
    {
        $summary = $this->summary();
        $period = $this->period();
        $source = (string) ($this->filters['source'] ?? KpiRegistry::SOURCE_MAINTENANCE);

        $scopeNote = match ($source) {
            KpiRegistry::SOURCE_HOUSEKEEPING_INDOOR => 'Housekeeping indoor dinilai per Area: kedisiplinan plan harian, SLA, dan dokumentasi.',
            KpiRegistry::SOURCE_HOUSEKEEPING_OUTDOOR => 'Housekeeping outdoor dinilai per Area: efisiensi terhadap window SLA, cakupan window, SLA, dan dokumentasi.',
            default => 'Maintenance dinilai per pelaksana (Performed By) — user yang mulai mengerjakan WO pada periode.',
        };

        return $schema
            ->components([
                Section::make('Filter')
                    ->icon(Heroicon::OutlinedFunnel)
                    ->description('Pilih sumber, fungsi/teknisi, dan periode lalu tekan Terapkan Filter.')
                    ->schema([
                        EmbeddedSchema::make('filterSchema'),
                    ])
                    ->footer([
                        Actions::make([
                            Action::make('resetFilters')
                                ->label('Reset')
                                ->icon(Heroicon::OutlinedArrowPath)
                                ->color('gray')
                                ->action('resetFilters'),
                            Action::make('applyFilters')
                                ->label('Terapkan Filter')
                                ->icon(Heroicon::OutlinedFunnel)
                                ->color('primary')
                                ->action('applyFilters'),
                        ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Ringkasan')
                    ->description('Gambaran umum hasil penilaian pada periode terpilih.')
                    ->icon(Heroicon::OutlinedPresentationChartLine)
                    ->schema([
                        View::make('filament.pages.kpi-summary-cards')
                            ->viewData([
                                'summary' => $summary,
                                'period' => $period,
                                'source' => $source,
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Metrik & Target')
                    ->description($scopeNote)
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->schema([
                        View::make('filament.pages.kpi-metric-cards')
                            ->viewData([
                                'metrics' => $summary['metrics'],
                                'results' => $this->results(),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Peringkat')
                    ->schema([
                        EmbeddedTable::make(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
