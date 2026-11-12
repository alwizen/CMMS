<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\HousekeepingDashboard;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Elemind\PressFilamentTheme\PressFilamentTheme;
use Filament\Enums\DatabaseNotificationsPosition;
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
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->maxContentWidth(Width::Full)
            ->globalSearch(position: GlobalSearchPosition::Sidebar)
            ->databaseNotifications(position: DatabaseNotificationsPosition::Sidebar)
            ->topbar(false)
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->databaseNotifications()
            ->sidebarFullyCollapsibleOnDesktop()
            ->profile()
            ->id('admin')
            ->path('admin')
            ->brandLogo(asset('img/logo.png'))
            // ->darkModeBrandLogo(asset('img/dark.png'))
            ->darkMode(false)
            ->favicon(asset('img/logo.svg'))
            ->brandLogoHeight('4.5rem')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->plugin(
                PressFilamentTheme::make()->telex()->rail(false)
            )

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                HousekeepingDashboard::class,
            ])
            ->navigationGroups([
                'Dashboard',
                'Maintenance',
                'Housekeeping',
                'Riwayat & Monitoring',
                'Master Data',
                'User & Permission',
                'Pengaturan',
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
                FilamentShieldPlugin::make()
                    ->navigationGroup('User & Permission'),
            ])
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_END,
                fn () => view('filament.auth.login-footer'),
            )
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
