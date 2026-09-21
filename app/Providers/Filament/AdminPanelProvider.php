<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Enums\GlobalSearchPosition;
use Filament\Enums\UserMenuPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use JohnRivera7\FilamentMia\MiaTheme;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->maxContentWidth(Width::Full)
            ->globalSearch(position: GlobalSearchPosition::Sidebar)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->id('admin')
            ->path('admin')
            ->brandName('CMMS')
            ->brandLogo(asset('img/rumat.png'))
            ->brandLogoHeight('3rem')
            ->login()
            // ->spa()
            // ->topNavigation()
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->colors([
                'primary' => Color::Amber,
            ])
            // ->plugin(
            //     MiaTheme::make()
            //         ->accentColor('#2752c9')
            //         ->secondaryColor('#c0c5e8')
            //         // ->font('Jost', 'Cormorant Garamond')
            //         ->font('Outfit', 'Fraunces')
            //         ->roundness('soft')
            //         ->density('comfortable')
            //         ->elevation(0.75)
            //         ->motion()
            //         ->darkMode(),
            // )

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
