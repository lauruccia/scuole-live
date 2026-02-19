<?php

namespace App\Providers;

use App\Models\ContractLessonSlot;
use App\Models\Lesson;
use App\Observers\ContractLessonSlotObserver;
use App\Observers\LessonMeetObserver;
use App\Observers\LessonObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

use Filament\Facades\Filament;
use Filament\Notifications\Auth\ResetPassword as FilamentResetPassword;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Forza lingua app
        App::setLocale('it');

        // Superadmin bypass permessi
        Gate::before(function ($user, $ability) {
            return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()
                ? true
                : null;
        });

        // Observer lezioni
        Lesson::observe(LessonObserver::class);
        Lesson::observe(LessonMeetObserver::class);

        // Observer slot -> rigenera lezioni in modo affidabile (afterCommit)
        ContractLessonSlot::observe(ContractLessonSlotObserver::class);

        /**
         * Override globale email reset password (vale anche per Filament).
         */
       ResetPassword::toMailUsing(function ($notifiable, string $token) {

    $path = request()->path();
    $firstSegment = explode('/', $path)[0] ?? 'admin';

    $panelId = match ($firstSegment) {
        'superadmin' => 'superadmin',
        'docente' => 'docente',
        'admin' => 'admin',
        default => 'admin',
    };

    // Nome rotta Filament per la pagina di reset password (con token)
    $routeName = "filament.{$panelId}.auth.password-reset.reset";

    $url = url(route($routeName, [
        'token' => $token,
        'email' => $notifiable->getEmailForPasswordReset(),
    ], false));

    return (new MailMessage)
        ->subject('Reimposta la password — A&A Language Center')
        ->view('emails.reset-password-brand', [
            'url' => $url,
            'notifiable' => $notifiable,
            'expire' => (int) config('auth.passwords.users.expire', 60),
        ]);
});
    }
}
