<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\DocumentNumber;
use App\Support\WorkOrderGenerator;
use App\Support\WorkOrderSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SiteSettings extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Site Setting';

    protected static ?string $title = 'Site Setting';

    protected static string|UnitEnum|null $navigationGroup = 'User & Permission';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.site-settings';

    /**
     * @var array<string, string>
     */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->currentFormats());
    }

    public function getSubheading(): ?string
    {
        return 'Atur format penomoran dokumen maintenance.';
    }

    /**
     * @return array<string, string>
     */
    protected function currentFormats(): array
    {
        $formats = [];

        foreach (array_keys(DocumentNumber::DEFAULTS) as $type) {
            $formats[$type] = DocumentNumber::format($type);
        }

        return [...$formats, ...$this->currentWorkOrderSettings()];
    }

    /**
     * @return array<string, mixed>
     */
    protected function currentWorkOrderSettings(): array
    {
        return [
            'wo_auto_generate' => WorkOrderSettings::autoGenerate(),
            'wo_lead_days' => WorkOrderSettings::leadDays(),
            'wo_start_time' => WorkOrderSettings::startTime(),
            'wo_skip_weekend' => WorkOrderSettings::skipWeekend(),
            'wo_backfill_days' => WorkOrderSettings::backfillDays(),
            'wo_expired_days' => WorkOrderSettings::expiredDays(),
        ];
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Format Penomoran')
                ->description('Gunakan token {YYYY}, {YY}, {MM}, {MONTH}, {DD}, {DATE}, dan {SEQ} untuk nomor urut. Contoh padding: {SEQ:6}.')
                ->schema([
                    $this->formatField('work_order'),
                    $this->formatField('maintenance_request'),
                    $this->formatField('housekeeping_plan'),
                    $this->formatField('housekeeping_request'),
                    $this->formatField('document'),
                    $this->formatField('finding'),
                ])
                ->columns(2),
            Section::make('Work Order Otomatis')
                ->description('Default nonaktif. Selama nonaktif, WO hanya dibuat manual lewat tombol Generate WO di dashboard. Jika diaktifkan, scheduler membuat WO preventif otomatis dari Equipment Type + interval checklist.')
                ->schema([
                    Toggle::make('wo_auto_generate')
                        ->label(WorkOrderSettings::LABELS[WorkOrderSettings::AUTO_GENERATE])
                        ->helperText('Scheduler berjalan setiap hari pukul 00:05. Pastikan cron `php artisan schedule:run` aktif di server.')
                        ->live(),
                    Toggle::make('wo_skip_weekend')
                        ->label(WorkOrderSettings::LABELS[WorkOrderSettings::SKIP_WEEKEND]),
                    Select::make('wo_lead_days')
                        ->label(WorkOrderSettings::LABELS[WorkOrderSettings::LEAD_DAYS])
                        ->options([1 => '1 hari', 2 => '2 hari', 3 => '3 hari', 4 => '4 hari', 5 => '5 hari'])
                        ->required()
                        ->helperText('WO dibuat untuk tanggal hari ini sampai N hari ke depan.'),
                    TextInput::make('wo_start_time')
                        ->label(WorkOrderSettings::LABELS[WorkOrderSettings::START_TIME])
                        ->type('time')
                        ->required(),
                    TextInput::make('wo_backfill_days')
                        ->label(WorkOrderSettings::LABELS[WorkOrderSettings::BACKFILL_DAYS])
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(365)
                        ->required()
                        ->helperText('0 = hanya jadwal ke depan. Isi >0 bila ingin mengejar jadwal yang terlewat saat scheduler sempat mati.'),
                    TextInput::make('wo_expired_days')
                        ->label(WorkOrderSettings::LABELS[WorkOrderSettings::EXPIRED_DAYS])
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(WorkOrderSettings::EXPIRED_DAYS_MAX)
                        ->required()
                        ->helperText('Batas masa berlaku WO otomatis sejak jam mulai. 0 = tanpa expired. Hanya berlaku untuk WO yang dibuat setelah pengaturan disimpan.'),
                ])
                ->columns(2),
        ]);
    }

    protected function formatField(string $type): TextInput
    {
        return TextInput::make($type)
            ->label(DocumentNumber::LABELS[$type])
            ->required()
            ->maxLength(100)
            ->rules([
                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (! DocumentNumber::hasSequenceToken((string) $value)) {
                        $fail('Format wajib mengandung token {SEQ} atau {SEQ:n}.');
                    }
                },
            ])
            ->helperText(fn (Get $get): string => 'Contoh hasil: '.DocumentNumber::preview(
                (string) ($get($type) ?: DocumentNumber::DEFAULTS[$type]),
            ));
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (array_keys(DocumentNumber::DEFAULTS) as $type) {
            DocumentNumber::setFormat($type, (string) $state[$type]);
        }

        Setting::setMany([
            WorkOrderSettings::AUTO_GENERATE => (bool) $state['wo_auto_generate'],
            WorkOrderSettings::LEAD_DAYS => (int) $state['wo_lead_days'],
            WorkOrderSettings::START_TIME => (string) $state['wo_start_time'],
            WorkOrderSettings::SKIP_WEEKEND => (bool) $state['wo_skip_weekend'],
            WorkOrderSettings::BACKFILL_DAYS => (int) $state['wo_backfill_days'],
            WorkOrderSettings::EXPIRED_DAYS => (int) $state['wo_expired_days'],
        ]);

        Notification::make()
            ->success()
            ->title('Pengaturan disimpan')
            ->send();
    }

    public function resetToDefaults(): void
    {
        $this->form->fill([
            ...DocumentNumber::DEFAULTS,
            'wo_auto_generate' => false,
            'wo_lead_days' => 3,
            'wo_start_time' => '08:00',
            'wo_skip_weekend' => true,
            'wo_backfill_days' => 0,
            'wo_expired_days' => 7,
        ]);

        Notification::make()
            ->info()
            ->title('Format dikembalikan ke default')
            ->body('Klik "Simpan Pengaturan" untuk menyimpan perubahan.')
            ->send();
    }

    /**
     * Jalankan generator WO otomatis sekarang (tanpa menunggu scheduler).
     */
    public function generateScheduledWorkOrders(): void
    {
        if (! WorkOrderSettings::autoGenerate()) {
            Notification::make()
                ->title('Work Order otomatis nonaktif')
                ->body('Aktifkan "Buat Work Order Otomatis" lalu simpan pengaturan terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        $result = app(WorkOrderGenerator::class)->generateScheduled(
            now()->subDays(WorkOrderSettings::backfillDays())->startOfDay(),
            now()->addDays(WorkOrderSettings::leadDays())->endOfDay(),
        );

        Notification::make()
            ->title('Generate WO otomatis selesai')
            ->body($result['created'].' WO dibuat, '.$result['existing'].' periode sudah ada.')
            ->success()
            ->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('generateScheduledWorkOrders')
                            ->label('Generate WO Sekarang')
                            ->icon(Heroicon::OutlinedBolt)
                            ->color('gray')
                            ->visible(fn (): bool => WorkOrderSettings::autoGenerate())
                            ->requiresConfirmation()
                            ->modalHeading('Jalankan Generator WO Otomatis')
                            ->modalDescription('Membuat WO untuk periode yang belum punya WO sesuai pengaturan yang tersimpan. Simpan pengaturan terlebih dahulu bila baru diubah.')
                            ->action('generateScheduledWorkOrders'),
                        Action::make('resetToDefaults')
                            ->label('Reset Default')
                            ->color('gray')
                            ->action('resetToDefaults'),
                        Action::make('save')
                            ->label('Simpan Pengaturan')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }
}
