<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentIcon;
use Filament\View\PanelsIconAlias;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override ikon tombol collapse/expand sidebar agar tidak terlihat seperti tombol "back".
        FilamentIcon::register([
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => \Filament\Support\Icons\Heroicon::OutlinedBars3BottomLeft,
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL => \Filament\Support\Icons\Heroicon::OutlinedBars3BottomRight,
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => \Filament\Support\Icons\Heroicon::OutlinedBars3BottomRight,
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL => \Filament\Support\Icons\Heroicon::OutlinedBars3BottomLeft,
        ]);
    }
}
