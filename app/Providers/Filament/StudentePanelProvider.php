<?php

namespace App\Providers\Filament;

use App\Filament\Common\Pages\ChangePasswordPage;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StudentePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('studente')
            ->path('studente')
            ->authGuard('web')
            ->login()
            ->passwordReset()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->renderHook(
                'panels::head.end',
                fn (): string => '<link rel="stylesheet" href="' . asset('css/student-calendar.css') . '?v=1">'
            )

            ->brandLogo(asset('images/logo-scuola.png'))
            ->brandLogoHeight('5rem')
            ->brandName('')

            ->plugins([
                \Saade\FilamentFullCalendar\FilamentFullCalendarPlugin::make(),
            ])

            ->discoverPages(
                in: app_path('Filament/Studente/Pages'),
                for: 'App\\Filament\\Studente\\Pages'
            )

            ->discoverWidgets(
                in: app_path('Filament/Studente/Widgets'),
                for: 'App\\Filament\\Studente\\Widgets'
            )

            ->discoverResources(
                in: app_path('Filament/Studente/Resources'),
                for: 'App\\Filament\\Studente\\Resources'
            )

            ->pages([
                Pages\Dashboard::class,
                ChangePasswordPage::class,
            ])

            ->widgets([
                Widgets\AccountWidget::class,
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
            ]);
    }
}