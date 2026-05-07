<?php

namespace App\Providers;

use App\Models\ContractLessonSlot;
use App\Models\Lesson;
use App\Models\SchoolSetting;
use App\Observers\ContractLessonSlotObserver;
use App\Observers\LessonMeetObserver;
use App\Observers\LessonObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
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

        // ── MAIL REDIRECT (solo test) ──────────────────────────────────────────
        // Se MAIL_REDIRECT_TO è valorizzato nel .env, tutte le email vengono
        // redirezionate a quell'indirizzo (TO, CC e BCC sostituiti).
        // Per disabilitare: rimuovere o svuotare MAIL_REDIRECT_TO nel .env
        // + cancellare la cache config (run_clear_cache.php sul server).
        if ($redirectTo = config('mail.redirect_to')) {
            Event::listen(MessageSending::class, function (MessageSending $event) use ($redirectTo) {
                $msg = $event->message;
                $msg->to($redirectTo);   // sostituisce TO
                $msg->cc();              // svuota CC
                $msg->bcc();             // svuota BCC
            });
        }

        // Forza lingua app + Carbon in italiano
        // Carbon::setLocale() è indipendente da App::setLocale() e necessario
        // per translatedFormat(), isoFormat() e diffForHumans() in italiano.
        App::setLocale('it');
        Carbon::setLocale('it');

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
         *
         * Determina il panel corretto in base al ruolo dell'utente:
         *   - superadmin / super_admin → panel 'superadmin'
         *   - Amministrazione / Segreteria → panel 'admin'
         *   - Docente → panel 'docente'
         *   - Studente / qualsiasi altro → panel 'studente'
         *
         * In questo modo il link nell'email punta sempre al panel giusto
         * e la pagina di reset funziona per tutti i ruoli.
         */
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            // Ricava il panel dal ruolo dell'utente
            $panelId = 'studente'; // fallback sicuro

            if (method_exists($notifiable, 'getRoleNames')) {
                $roles = $notifiable->getRoleNames();

                if ($roles->intersect(['superadmin', 'super_admin', 'Superadmin'])->isNotEmpty()) {
                    $panelId = 'superadmin';
                } elseif ($roles->intersect(['Amministrazione', 'Segreteria', 'admin'])->isNotEmpty()) {
                    $panelId = 'admin';
                } elseif ($roles->contains('Docente')) {
                    $panelId = 'docente';
                }
            }

            $routeName = "filament.{$panelId}.auth.password-reset.reset";

            // Se la route non esiste (panel non ancora registrato o typo),
            // torniamo al fallback sicuro evitando un'eccezione in produzione.
            if (! \Illuminate\Support\Facades\Route::has($routeName)) {
                $routeName = 'filament.admin.auth.password-reset.reset';
            }

            $url = URL::temporarySignedRoute(
                $routeName,
                now()->addMinutes((int) config('auth.passwords.users.expire', 60)),
                [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ]
            );

            return (new MailMessage)
                ->subject('Reimposta la password — ' . SchoolSetting::schoolName())
                ->view('emails.reset-password-brand', [
                    'url'        => $url,
                    'notifiable' => $notifiable,
                    'expire'     => (int) config('auth.passwords.users.expire', 60),
                ]);
        });
    }
}