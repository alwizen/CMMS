<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\FieldDashboard;
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

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->maxContentWidth(Width::Full)
            ->globalSearch(position: GlobalSearchPosition::Sidebar)
            ->databaseNotifications(position: DatabaseNotificationsPosition::Sidebar)
            ->topbar(false)
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            // Petugas lapangan mengerjakan checklist/foto langsung dari halaman
            // detail (View), jadi relation manager tidak boleh read-only.
            ->readOnlyRelationManagersOnResourceViewPagesByDefault(false)
            ->profile()
            ->brandLogo(asset('img/logo.png'))
            ->darkMode(false)
            ->favicon(asset('img/logo.svg'))
            ->brandLogoHeight('4.5rem')
            ->login(fn () => redirect('/login'))
            ->colors([
                'primary' => Color::Teal,
            ])
            ->plugin(
                PressFilamentTheme::make()->telex()->rail(false)
            )
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\Filament\App\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\Filament\App\Pages')
            ->pages([
                FieldDashboard::class,
            ])
            ->navigationGroups([
                'Pekerjaan',
                'Housekeeping',
                'Aset',
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
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
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn () => view('filament.auth.login-background'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn () => view('filament.auth.login-footer'),
            )
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
