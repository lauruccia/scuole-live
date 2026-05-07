<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Common\Pages\ChangePasswordPage;

class SuperadminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('superadmin')
            ->path('superadmin')
            ->authGuard('web')
            ->login()
            ->passwordReset()

            ->brandLogo(asset('images/logo-scuola.png'))
            ->brandLogoHeight('5rem')
            ->brandName('')

            ->colors(['primary' => Color::Amber])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->navigationGroups([
                'Didattica',
                'Studenti',
                'Risorse Umane',
                'Report',
                'CRM',
                'Pagamenti',
                'Comunicazioni',
                'Email',
                'Impostazioni',
                'Configurazione',
            ])

            ->pages([
    Pages\Dashboard::class,
    ChangePasswordPage::class,
])

            ->widgets([
                \App\Filament\Widgets\LessonsTodayWidget::class,
                \App\Filament\Widgets\ReportLinks::class,
                \App\Filament\Widgets\ContractStatusWidget::class,
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

            ->plugins([
                FilamentShieldPlugin::make(),
                \Saade\FilamentFullCalendar\FilamentFullCalendarPlugin::make(),
            ])

            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\ForceChangePassword::class,
            ])

            ->bootUsing(function () {
                // IMPORTANT: durante password reset l'utente NON è loggato,
                // quindi non blocchiamo nulla.
                if (! auth()->check()) return;

                $super = (string) (config('filament-shield.super_admin.name') ?? 'super_admin');
                abort_unless(auth()->user()->hasRole($super), 403);
            });
    }
}
