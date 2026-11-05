<?php

namespace App\Filament\Pages;

use App\Support\DocumentNumber;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
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
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Site Setting';

    protected static ?string $title = 'Site Setting';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

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

        return $formats;
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
                    $this->formatField('maintenance_plan'),
                    $this->formatField('maintenance_schedule'),
                    $this->formatField('maintenance_request'),
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

        Notification::make()
            ->success()
            ->title('Pengaturan penomoran disimpan')
            ->send();
    }

    public function resetToDefaults(): void
    {
        $this->form->fill(DocumentNumber::DEFAULTS);

        Notification::make()
            ->info()
            ->title('Format dikembalikan ke default')
            ->body('Klik "Simpan Pengaturan" untuk menyimpan perubahan.')
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
