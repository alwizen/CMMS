<?php

namespace App\Filament\Pages;

use App\Filament\Widgets;
use App\Models\Activity;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\HousekeepingArea;
use App\Models\HousekeepingManpower;
use App\Models\User;
use App\Support\HousekeepingPlanGenerator;
use App\Support\WorkOrderGenerator;
use App\Support\WorkOrderSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    /**
     * Teruskan perubahan filter ke widget (yang tidak lagi lazy).
     */
    public function updatedFilters(): void
    {
        $this->dispatch('dashboard-filters-updated', filters: $this->filters);
    }

    protected static ?string $title = 'Maintenance Dashboard';

    protected static ?string $navigationLabel = 'Maintenance Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    public function getTitle(): string|Htmlable
    {
        return 'Maintenance Dashboard';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Maintenance Dashboard';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Selamat datang, '.(auth()->user()?->name ?? 'Pengguna');
    }

    /**
     * Interval yang tersedia diambil dari checklist maintenance (Activity)
     * milik equipment type dari equipment yang dipilih.
     *
     * @param  array<int, int|string>|null  $equipmentIds
     * @return array<string, string>
     */
    public function intervalOptions(?array $equipmentIds): array
    {
        if (blank($equipmentIds)) {
            return [];
        }

        $typeIds = Equipment::query()
            ->whereIn('id', $equipmentIds)
            ->whereNotNull('equipment_type_id')
            ->pluck('equipment_type_id')
            ->unique()
            ->all();

        if (empty($typeIds)) {
            return [];
        }

        $intervals = Activity::query()
            ->whereIn('equipment_type_id', $typeIds)
            ->where('status', true)
            ->whereNotNull('interval')
            ->pluck('interval')
            ->unique()
            ->all();

        return array_intersect_key(
            Activity::INTERVALS,
            array_flip($intervals),
        );
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('generateWorkOrders')
                ->label('Generate WO')
                ->icon(Heroicon::OutlinedBolt)
                ->color('primary')
                ->modalHeading('Generate Work Order')
                ->modalDescription('Membuat Work Order langsung (status Pending). Pilih Equipment Type yang punya Penanggung Jawab & checklist sesuai interval. Equipment lain dilewati.')
                ->form([
                    Forms\Components\Select::make('equipment_type_id')
                        ->label('Equipment Type')
                        ->options(fn (): array => EquipmentType::query()
                            ->whereNotNull('responsible_user_id')
                            ->whereHas('activities', fn ($query) => $query->where('status', true))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->live()
                        ->required()
                        ->placeholder('Pilih tipe equipment')
                        ->afterStateUpdated(function (Set $set): void {
                            $set('equipment_ids', null);
                            $set('interval', null);
                        })
                        ->helperText('Hanya tipe dengan penanggung jawab & checklist yang muncul.'),
                    Forms\Components\Select::make('equipment_ids')
                        ->label('Equipment')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->options(fn (Get $get): array => Equipment::query()
                            ->where('equipment_type_id', $get('equipment_type_id'))
                            ->orderBy('tag_number')
                            ->pluck('tag_number', 'id')
                            ->all())
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('interval', null))
                        ->helperText('Equipment dari tipe yang dipilih.'),
                    Forms\Components\Select::make('interval')
                        ->label('Interval')
                        ->options(fn (Get $get): array => $this->intervalOptions($get('equipment_ids')))
                        ->required()
                        ->live()
                        ->placeholder('Pilih interval')
                        ->helperText('Interval mengikuti checklist maintenance equipment yang dipilih.'),
                    Forms\Components\TextInput::make('quantity')
                        ->label('Berapa WO per equipment')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->helperText('Jumlah WO yang dibuat untuk tiap equipment terpilih.'),
                    Forms\Components\TimePicker::make('start_time')
                        ->label('Jam Mulai')
                        ->seconds(false)
                        ->default(fn (): string => WorkOrderSettings::startTime())
                        ->required(),
                    Forms\Components\Select::make('issued_by')
                        ->label('Pemberi Tugas')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->default(fn (): ?int => auth()->id())
                        ->searchable()
                        ->required(),
                    Forms\Components\Toggle::make('skip_weekend')
                        ->label('Lewati Sabtu & Minggu')
                        ->default(fn (): bool => WorkOrderSettings::skipWeekend()),
                ])
                ->action(function (array $data): void {
                    $result = app(WorkOrderGenerator::class)->generateDirect(
                        equipmentIds: $data['equipment_ids'],
                        interval: $data['interval'],
                        quantity: (int) $data['quantity'],
                        startTime: $data['start_time'],
                        skipWeekend: (bool) ($data['skip_weekend'] ?? false),
                        issuedBy: (int) $data['issued_by'],
                    );

                    if ($result['created'] === 0) {
                        $lines = [];

                        foreach ($result['skip_reasons'] ?? [] as $tag => $reason) {
                            $lines[] = $tag.': '.$reason;
                        }

                        Notification::make()
                            ->title('Tidak ada Work Order yang dibuat')
                            ->body($lines === []
                                ? 'Tidak ada equipment yang memenuhi syarat.'
                                : 'Semua equipment dilewati — '.implode('; ', $lines).'.')
                            ->warning()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $body = sprintf('%d WO dibuat.', $result['created']);

                    if (! empty($result['skip_reasons'])) {
                        $lines = [];

                        foreach ($result['skip_reasons'] as $tag => $reason) {
                            $lines[] = $tag.' ('.$reason.')';
                        }

                        $body .= ' Dilewati: '.implode(', ', $lines).'.';
                    }

                    Notification::make()
                        ->title('Generate WO selesai')
                        ->body($body)
                        ->success()
                        ->send();
                }),
            Action::make('generateHousekeepingPlans')
                ->label('Generate Plan Harian')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->color('success')
                ->modalHeading('Generate Plan Harian Housekeeping')
                ->modalDescription('Membuat Plan Harian (status Draft) berisi checklist dari master checklist fasilitas di area terpilih. Area tanpa checklist aktif dilewati.')
                ->form([
                    Forms\Components\Select::make('area_ids')
                        ->label('Area Housekeeping')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->options(fn (): array => HousekeepingArea::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->helperText('Satu plan dibuat untuk tiap area terpilih.'),
                    Forms\Components\DatePicker::make('plan_date')
                        ->label('Tanggal Plan')
                        ->required()
                        ->default(now()),
                    Forms\Components\Select::make('manpower_ids')
                        ->label('Manpower')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn (Get $get): array => HousekeepingManpower::query()
                            ->whereIn('housekeeping_area_id', $get('area_ids') ?? [])
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->helperText('Kosongkan untuk menugaskan semua manpower aktif di tiap area.'),
                    Forms\Components\Textarea::make('note')
                        ->label('Catatan')
                        ->rows(2)
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $result = app(HousekeepingPlanGenerator::class)->generate(
                        areaIds: $data['area_ids'],
                        planDate: $data['plan_date'],
                        manpowerIds: $data['manpower_ids'] ?? [],
                        note: $data['note'] ?? null,
                        createdBy: auth()->id(),
                    );

                    if ($result['created'] === 0) {
                        $lines = [];

                        foreach ($result['skip_reasons'] as $area => $reason) {
                            $lines[] = $area.': '.$reason;
                        }

                        Notification::make()
                            ->title('Tidak ada Plan Harian yang dibuat')
                            ->body($lines === []
                                ? 'Tidak ada area yang memenuhi syarat.'
                                : 'Semua area dilewati — '.implode('; ', $lines).'.')
                            ->warning()
                            ->persistent()
                            ->send();

                        return;
                    }

                    $body = sprintf('%d Plan Harian dibuat.', $result['created']);

                    if (! empty($result['skip_reasons'])) {
                        $lines = [];

                        foreach ($result['skip_reasons'] as $area => $reason) {
                            $lines[] = $area.' ('.$reason.')';
                        }

                        $body .= ' Dilewati: '.implode(', ', $lines).'.';
                    }

                    Notification::make()
                        ->title('Generate Plan Harian selesai')
                        ->body($body)
                        ->success()
                        ->send();
                }),
            FilterAction::make()
                ->schema([
                    Forms\Components\Select::make('dateRange')
                        ->label('Periode')
                        ->options([
                            'all' => 'Semua Waktu',
                            'today' => 'Hari Ini',
                            'yesterday' => 'Kemarin',
                            'week' => '7 Hari Terakhir',
                            'month' => '1 Bulan Terakhir',
                            'custom' => 'Pilih Range',
                        ])
                        ->default('all')
                        ->live()
                        ->reactive(),
                    Forms\Components\DatePicker::make('dateFrom')
                        ->label('Dari Tanggal')
                        ->visible(fn ($get) => $get('dateRange') === 'custom'),
                    Forms\Components\DatePicker::make('dateTo')
                        ->label('Sampai Tanggal')
                        ->visible(fn ($get) => $get('dateRange') === 'custom'),
                ]),
        ];
    }

    public function getWidgets(): array
    {
        return [
            // Widgets\StatsOverviewWidget::class,
            Widgets\CompanyAreaMapWidget::class,
            // Widgets\WorkOrderClassificationChart::class,
            Widgets\WorkOrderStatusChart::class,
            Widgets\MaintenanceTrendChart::class,
            Widgets\RecentWorkOrdersWidget::class,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\StatsOverviewWidget::class,
        ];
    }
}
