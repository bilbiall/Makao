<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

//for user editing profile
//use Filament\Pages\Auth\EditProfile;
//use Filament\Account\Pages\EditProfile;

use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;

//alerts
use TomatoPHP\FilamentAlerts\FilamentAlertsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->plugins([
                FilamentEditProfilePlugin::make(),
            ])
            //for notifications
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->login()
            ->colors([
                'primary' => \App\Support\BrandPalette::filamentColor(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            // discoverPages already picks up App\Filament\Pages\Dashboard (this project's
            // real dashboard). Previously this array ALSO explicitly registered Filament's
            // stock Pages\Dashboard::class alongside it - two different classes both
            // claiming the "dashboard" page/route name, which silently "worked" at
            // runtime (Laravel tolerates duplicate route names outside of route caching)
            // but broke `php artisan route:cache` with a route-name collision.
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            // This panel is deliberately "advanced mode" - the mobile-first app shell
            // (/app/admin/...) is the default landing spot after login. This link is
            // the way back for anyone (admin/landlord/manager/caretaker) who came here.
            ->userMenuItems([
                MenuItem::make()
                    ->label('Back to App')
                    ->url(fn () => route('app.admin.dashboard'))
                    ->icon('heroicon-o-device-phone-mobile'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureAdminRole::class,
            ]);
    }

}
