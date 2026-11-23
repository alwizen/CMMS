<?php

namespace App\Filament\Pages;

use App\Filament\Actions\PrintDailyReportAction;
use App\Filament\Actions\ResetDashboardFilterAction;
use App\Filament\Widgets;
use App\Http\Middleware\RedirectFromMaintenanceDashboard;
use App\Models\Activity;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\User;
use App\Support\WorkOrderGenerator;
use App\Support\WorkOrderSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    /**
     * Maintenance Dashboard hanya untuk super admin, SPV MOS, SPV HSSE, dan
     * pengawas. SPV GOS punya dashboard housekeeping sendiri.
     *
     * @var list<string>
     */
    public const ALLOWED_ROLES = [
        'super_admin',
        'spv_mos',
        'spv_hsse',
        'pengawas',
    ];

    /**
     * User yang tidak berhak diarahkan ke Housekeeping Dashboard, bukan 403.
     *
     * @var string|array<string>
     */
    protected static string|array $routeMiddleware = [
        RedirectFromMaintenanceDashboard::class,
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(self::ALLOWED_ROLES) ?? false;
    }

    /**
     * Filter global tidak disimpan di URL/session: setiap refresh atau
     * perpindahan halaman kembali ke default.
     */
    public ?array $filters = null;

    public function persistsFiltersInSession(): bool
    {
        return false;
    }

    /**
     * Teruskan perubahan filter ke widget (yang tidak lagi lazy).
     */
    public function updatedFilters(): void
    {
        $this->dispatch('dashboard-filters-updated', filters: $this->filters);
    }

    protected static ?string $title = 'Maintenance Dashboard';

    protected static ?string $navigationLabel = 'Maintenance Dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Maintenance';

    protected static ?int $navigationSort = -10;

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
                ->label('Generate Work Order')
                ->icon(Heroicon::OutlinedBolt)
                ->color('primary')
                ->modalHeading('Generate Work Order')
                ->modalDescription('Membuat Work Order langsung (status Pending). Pilih Fungsi lalu Equipment Type yang punya checklist sesuai interval. Equipment lain dilewati.')
                ->schema([
                    Section::make('Target Equipment')
                        ->description('Pilih fungsi, tipe, dan equipment yang akan dibuatkan Work Order.')
                        ->icon(Heroicon::OutlinedFunnel)
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('fungsi')
                                ->label('Fungsi')
                                ->options(User::FUNGSI)
                                ->required()
                                ->live()
                                ->searchable()
                                ->placeholder('Pilih fungsi')
                                ->afterStateUpdated(function (Set $set): void {
                                    $set('equipment_type_id', null);
                                    $set('equipment_ids', null);
                                    $set('interval', null);
                                })
                                ->helperText('Menentukan penanggung jawab WO & tipe equipment yang ditampilkan.'),
                            Forms\Components\Select::make('equipment_type_id')
                                ->label('Equipment Type')
                                ->options(fn (Get $get): array => EquipmentType::query()
                                    ->where('fungsi', $get('fungsi'))
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
                                ->helperText('Hanya tipe dengan fungsi terpilih & checklist yang muncul.'),
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
                                ->helperText('Equipment dari tipe yang dipilih.')
                                ->columnSpanFull(),
                        ]),

                    Section::make('Jadwal & Checklist')
                        ->description('Tentukan interval checklist, jumlah WO, serta masa berlaku.')
                        ->icon(Heroicon::OutlinedCalendarDays)
                        ->columns(2)
                        ->schema([
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
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Start Date')
                                ->required()
                                ->default(now())
                                ->helperText('Tanggal mulai WO pertama.'),
                            Forms\Components\TimePicker::make('start_time')
                                ->label('Jam Mulai')
                                ->seconds(false)
                                ->default(fn (): string => WorkOrderSettings::startTime())
                                ->required(),
                            Forms\Components\TextInput::make('expired_days')
                                ->label('Expired (hari)')
                                ->numeric()
                                ->minValue(0)
                                ->default(7)
                                ->required()
                                ->helperText('Masa berlaku WO dalam hari sejak start date. Contoh 7 = 7 hari.'),
                            Forms\Components\Toggle::make('skip_weekend')
                                ->label('Lewati Sabtu & Minggu')
                                ->default(fn (): bool => WorkOrderSettings::skipWeekend()),
                            Forms\Components\Toggle::make('allow_duplicate')
                                ->label('Izinkan Duplikat')
                                ->default(false)
                                ->helperText('Jika mati, WO dengan interval sama pada periode yang sama akan dilewati.'),
                        ]),

                    Section::make('Penugasan')
                        ->description('Pemberi tugas untuk Work Order yang dibuat.')
                        ->icon(Heroicon::OutlinedUserCircle)
                        ->schema([
                            Forms\Components\Select::make('issued_by')
                                ->label('Pemberi Tugas')
                                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->default(fn (): ?int => auth()->id())
                                ->searchable()
                                ->required(),
                        ]),
                ])
                ->action(function (array $data): void {
                    $result = app(WorkOrderGenerator::class)->generateDirect(
                        equipmentIds: $data['equipment_ids'],
                        interval: $data['interval'],
                        quantity: (int) $data['quantity'],
                        startTime: $data['start_time'],
                        skipWeekend: (bool) ($data['skip_weekend'] ?? false),
                        issuedBy: (int) $data['issued_by'],
                        startDate: $data['start_date'] ?? null,
                        expiredDays: (int) ($data['expired_days'] ?? 0),
                        fungsi: $data['fungsi'] ?? null,
                        allowDuplicate: (bool) ($data['allow_duplicate'] ?? false),
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

                    if (! empty($result['duplicates'])) {
                        $lines = [];

                        foreach ($result['duplicates'] as $tag => $reason) {
                            $lines[] = $tag.' ('.$reason.')';
                        }

                        $body .= ' Duplikat dilewati: '.implode(', ', $lines).'.';
                    }

                    Notification::make()
                        ->title('Generate WO selesai')
                        ->body($body)
                        ->success()
                        ->send();
                }),
            PrintDailyReportAction::make('maintenance'),
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
                    Forms\Components\Select::make('fungsi')
                        ->label('Fungsi')
                        ->options(User::FUNGSI)
                        ->searchable()
                        ->live()
                        ->placeholder('Semua fungsi')
                        ->afterStateUpdated(fn (Set $set) => $set('user_id', null)),
                    Forms\Components\Select::make('user_id')
                        ->label('Teknisi / Penanggung Jawab')
                        ->options(fn (Get $get): array => filled($get('fungsi'))
                            ? User::optionsByFungsi((string) $get('fungsi'))
                            : [])
                        ->searchable()
                        ->live()
                        ->disabled(fn (Get $get): bool => blank($get('fungsi')))
                        ->placeholder(fn (Get $get): string => blank($get('fungsi'))
                            ? 'Pilih fungsi dulu'
                            : 'Semua teknisi'),
                ]),
            ResetDashboardFilterAction::make(),
        ];
    }

    public function getWidgets(): array
    {
        return [
            // Widgets\StatsOverviewWidget::class,
            Widgets\MaintenanceKpiRankingWidget::class,
            // Widgets\WorkOrderClassificationChart::class,
            Widgets\WorkOrderStatusChart::class,
            Widgets\MaintenanceTrendChart::class,
            Widgets\CompanyAreaMapWidget::class,
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
