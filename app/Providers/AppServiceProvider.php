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
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Models\Student;
use App\Observers\StudentObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Student::observe(StudentObserver::class);
        
        // Forza lingua app
        App::setLocale('it');

        // Forza HTTPS in produzione
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

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
         * Override globale email reset password.
         * Temporaneamente forzato sul pannello docente
         * per generare link corretti tipo /docente/password-reset/reset
         */
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $panelId = 'docente';

            $routeName = "filament.{$panelId}.auth.password-reset.reset";

            $url = URL::temporarySignedRoute(
                $routeName,
                now()->addMinutes((int) config('auth.passwords.users.expire', 60)),
                [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ]
            );

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