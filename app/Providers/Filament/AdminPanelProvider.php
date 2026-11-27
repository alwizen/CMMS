<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\HousekeepingDashboard;
use App\Http\Middleware\AuthenticatePanel;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Elemind\PressFilamentTheme\Enums\PressVariant;
use Elemind\PressFilamentTheme\PressFilamentTheme;
use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Enums\GlobalSearchPosition;
use Filament\Enums\UserMenuPosition;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->maxContentWidth(Width::Full)
            // ->globalSearch(position: GlobalSearchPosition::Sidebar)
            // ->databaseNotifications(position: DatabaseNotificationsPosition::Sidebar)
            // ->topbar(false)
            // ->userMenu(position: UserMenuPosition::Sidebar)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandLogo(asset('img/lg.png'))
            ->darkMode(false)
            ->favicon(asset('img/logo.svg'))
            ->brandLogoHeight('2.8rem')
            ->login(fn() => redirect('/login'))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->plugin(
                PressFilamentTheme::make()->runtimeSwitch([PressVariant::Telex, PressVariant::Broadsheet, PressVariant::Gutter, PressVariant::Vellum])->rail(false)
            )

            // Didaftarkan sebelum discovery agar urutan sidebar mengikuti daftar ini.
            ->navigationGroups($this->navigationGroups())

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                HousekeepingDashboard::class,
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
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        shouldRegisterNavigation: false,
                        navigationGroup: 'User & Permission',
                    )
                    ->enableTwoFactorAuthentication(force: false)
                    ->enableSanctumTokens()
                    ->enableBrowserSessions(),
            ])
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn() => view('filament.auth.login-background'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn() => view('filament.auth.login-footer'),
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                function (): HtmlString {
                    if (filament('press-filament-theme')->resolveVariant() !== PressVariant::Telex) {
                        return new HtmlString('');
                    }

                    return new HtmlString(<<<'HTML'
                        <style data-press-telex-topbar>
                            html:not(.dark) {
                                --press-bg-topbar: var(--press-bg-surface);
                                --press-rail-logo-filter: none;
                                --press-rail-logo-color: var(--press-neutral-950);
                            }
                        </style>
                        HTML);
                },
            )
            ->authMiddleware([
                AuthenticatePanel::class,
            ]);
    }

    /**
     * Grup menu panel admin. Urutan array menentukan urutan di sidebar;
     * ubah label/ikon/collapsed cukup di sini.
     *
     * @return array<int, NavigationGroup>
     */
    protected function navigationGroups(): array
    {
        return [
            NavigationGroup::make('Maintenance')
                ->icon(Heroicon::OutlinedWrenchScrewdriver),
            NavigationGroup::make('Housekeeping')
                ->icon(Heroicon::OutlinedSparkles),
            NavigationGroup::make('Performance')
                ->icon(Heroicon::OutlinedRocketLaunch),
            NavigationGroup::make('Master Data')
                ->icon(Heroicon::OutlinedCircleStack),
            NavigationGroup::make('User & Permission')
                ->icon(Heroicon::OutlinedShieldCheck),
        ];
    }
}
