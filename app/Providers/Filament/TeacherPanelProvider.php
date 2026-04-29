<?php

namespace App\Providers\Filament;

use App\Filament\Pages\LessonCalendar;
use App\Filament\Resources\LessonResource;
use App\Filament\Teacher\Pages\MieiStudentiPage;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
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
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use App\Filament\Common\Pages\ChangePasswordPage;

class TeacherPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('docente')
            ->path('docente')
            ->authGuard('web')

            ->login()
            ->passwordReset()

            // ✅ registra il plugin anche nel panel docente
            ->plugins([
                FilamentFullCalendarPlugin::make(),
            ])

            ->colors([
                'primary' => Color::Blue,
            ])

            ->brandLogo(asset('images/logo-scuola.png'))
            ->brandLogoHeight('5rem')
            ->brandName('')

            ->pages([
                Dashboard::class,
                MieiStudentiPage::class,
                LessonCalendar::class,
                ChangePasswordPage::class,
            ])

            ->resources([
                LessonResource::class,
                \App\Filament\Teacher\Resources\TeacherHomeworkResource::class,
                \App\Filament\Teacher\Resources\TeacherMaterialResource::class,
            ])

            ->navigationGroups([
                'Didattica',
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
