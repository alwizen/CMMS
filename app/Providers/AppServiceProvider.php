<?php

namespace App\Providers;

use App\Filament\Auth\LogoutResponse;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Logout dari panel mana pun kembali ke satu halaman login.
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override ikon tombol collapse/expand sidebar agar tidak terlihat seperti tombol "back".
        FilamentIcon::register([
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => Heroicon::OutlinedBars3BottomLeft,
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL => Heroicon::OutlinedBars3BottomRight,
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => Heroicon::OutlinedBars3BottomRight,
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL => Heroicon::OutlinedBars3BottomLeft,
        ]);
    }
}
