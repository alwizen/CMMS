<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\UserInvitation;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createUser')
                ->label('Buat User')
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('gray')
                ->visible(fn (): bool => (bool) auth()->user()?->hasRole('super_admin'))
                ->modalHeading('Buat User')
                ->modalDescription('Buat akun aktif langsung dengan password awal. Pengguna dapat mengganti password sendiri lewat menu profil.')
                ->modalSubmitActionLabel('Simpan User')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(table: 'users', column: 'email'),
                    TextInput::make('password')
                        ->label('Password Awal')
                        ->password()
                        ->revealable()
                        ->required()
                        ->rule(Password::min(8))
                        ->maxLength(255),
                    Select::make('role')
                        ->label('Role')
                        ->options(fn (): array => Role::query()
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->all())
                        ->searchable()
                        ->required()
                        ->placeholder('Pilih role'),
                    Select::make('fungsi')
                        ->label('Fungsi')
                        ->options(User::FUNGSI)
                        ->searchable()
                        ->nullable()
                        ->placeholder('Opsional'),
                    Toggle::make('status')
                        ->label('Aktif')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => Str::lower(trim((string) $data['email'])),
                        'password' => $data['password'],
                        'fungsi' => $data['fungsi'] ?? null,
                        'status' => (bool) ($data['status'] ?? true),
                        'email_verified_at' => now(),
                    ]);

                    $user->syncRoles([$data['role']]);

                    Notification::make()
                        ->title('User dibuat')
                        ->body('Akun '.$user->email.' berhasil dibuat.')
                        ->success()
                        ->send();
                }),
            Action::make('inviteUser')
                ->label('Invite Pengguna')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('primary')
                ->visible(fn (): bool => (bool) auth()->user()?->hasRole('super_admin'))
                ->modalHeading('Undang Pengguna')
                ->modalDescription('Pengguna akan menerima email berisi tautan untuk membuat password sendiri.')
                ->modalSubmitActionLabel('Kirim Undangan')
                ->schema([
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            $email = Str::lower(trim((string) $value));

                            $existing = User::query()
                                ->whereRaw('LOWER(email) = ?', [$email])
                                ->first();

                            if ($existing && ! $existing->hasPendingInvitation()) {
                                $fail('Email ini sudah terdaftar. Gunakan email lain.');
                            }
                        }),
                    Select::make('role')
                        ->label('Role')
                        ->options(fn (): array => Role::query()
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->all())
                        ->searchable()
                        ->required()
                        ->placeholder('Pilih role'),
                    Select::make('fungsi')
                        ->label('Fungsi')
                        ->options(User::FUNGSI)
                        ->searchable()
                        ->nullable()
                        ->placeholder('Opsional'),
                ])
                ->action(function (array $data): void {
                    $email = Str::lower(trim((string) $data['email']));

                    $user = User::query()
                        ->whereRaw('LOWER(email) = ?', [$email])
                        ->first();

                    if ($user) {
                        $user->forceFill(['fungsi' => $data['fungsi'] ?? null])->save();
                    } else {
                        $user = User::create([
                            'name' => Str::headline(Str::before($email, '@')),
                            'email' => $email,
                            'password' => Str::password(32),
                            'fungsi' => $data['fungsi'] ?? null,
                            'status' => false,
                        ]);
                    }

                    $user->syncRoles([$data['role']]);

                    UserInvitation::issue($user);

                    Notification::make()
                        ->title('Undangan terkirim')
                        ->body('Email undangan dikirim ke '.$user->email.'.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
