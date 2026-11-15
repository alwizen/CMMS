<?php

namespace App\Filament\Pages;

use App\Filament\Widgets;
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
use Filament\Schemas\Components\Utilities\Get;
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
                        ->helperText('Equipment dari tipe yang dipilih.'),
                    Forms\Components\Select::make('interval')
                        ->label('Interval')
                        ->options(Activity::INTERVALS)
                        ->required()
                        ->placeholder('Pilih interval'),
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
