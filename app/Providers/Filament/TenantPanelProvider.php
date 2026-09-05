<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use Filament\Navigation\NavigationGroup;
use Filament\Pages\Account;

use App\Filament\Tenant\Pages\TenantDashboard;


//for user editing profile
//use Filament\Pages\Auth\EditProfile;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;

use TomatoPHP\FilamentAlerts\FilamentAlertsPlugin;





class TenantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $logoPath = \App\Models\Setting::forLandlord(null)->payload['logo_path'] ?? null;

        $panel = $panel
            ->id('tenant')
            ->path('tenant')
            ->favicon(asset('favicon-32.png'))
            ->plugins([
                FilamentEditProfilePlugin::make(),
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->login()
            ->colors([
                'primary' => \App\Support\BrandPalette::filamentColor(),
            ])
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\\Filament\\Tenant\\Pages')
            ->pages([
                TenantDashboard::class,
                \App\Filament\Pages\Chat::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\\Filament\\Tenant\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Back to App')
                    ->url(fn () => route('app.tenant.dashboard'))
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
                \App\Http\Middleware\EnsureTenantRole::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('My Records'),
            ]);

        if ($logoPath) {
            $panel->brandLogo(\Illuminate\Support\Facades\Storage::disk('public')->url($logoPath));
        }

        return $panel;
    }
}
