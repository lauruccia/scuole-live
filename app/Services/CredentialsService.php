<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Reinvio credenziali di accesso: genera una nuova password temporanea,
 * la salva sull'utente collegato e la invia via email usando il sistema
 * di template (evento "student.credentials_resend", con fallback integrato).
 *
 * L'utente deve cambiarla al primo accesso (must_change_password → middleware
 * ForceChangePassword).
 */
class CredentialsService
{
    public const EVENT = 'student.credentials_resend';

    /**
     * @return string indirizzo email a cui sono state inviate le credenziali
     *
     * @throws \RuntimeException se mancano i dati o l'invio non riesce
     */
    public function resendToStudent(Student $student): string
    {
        $recipient = $this->recipientFor($student);
        $user      = $this->resolveUser($student);

        $hasFlag  = Schema::hasColumn('users', 'must_change_password');
        $previous = [
            'password'             => $user->getRawOriginal('password'),
            'must_change_password' => $hasFlag ? $user->getRawOriginal('must_change_password') : null,
        ];

        $plain = $this->generatePassword();

        $update = ['password' => Hash::make($plain)];
        if ($hasFlag) {
            $update['must_change_password'] = true;
        }
        $user->forceFill($update)->save();

        $first = $student->first_name ?: $user->name;

        $sent = false;
        try {
            $sent = app(EmailTemplateService::class)->sendTemplate(
                $this->template(),
                $recipient,
                $first,
                [
                    'nome'        => e($first),
                    'cognome'     => e((string) $student->last_name),
                    'email'       => e($user->email),
                    'password'    => e($plain),
                    'portale_url' => url('/studente'),
                    'app_name'    => e(config('app.name', 'A&A Language Center')),
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }

        if (! $sent) {
            // Invio fallito: ripristina lo stato precedente, così l'utente non
            // resta con una password che nessuno conosce.
            $restore = ['password' => $previous['password']];
            if ($hasFlag) {
                $restore['must_change_password'] = $previous['must_change_password'];
            }
            $user->forceFill($restore)->saveQuietly();

            throw new \RuntimeException('Invio email non riuscito: la password non è stata modificata. Controlla la configurazione della posta e riprova.');
        }

        // Audit (senza la password)
        if (function_exists('activity')) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($student)
                ->withProperties(['recipient' => $recipient])
                ->log('Credenziali di accesso reinviate');
        }

        return $recipient;
    }

    /** Destinatario: email dello studente; se minorenne senza email, quella del genitore. */
    public function recipientFor(Student $student): string
    {
        $email = trim((string) $student->email);

        if ($email === '' && $student->is_minor) {
            $email = trim((string) $student->parent_email);
        }

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Lo studente non ha un indirizzo email valido a cui inviare le credenziali.');
        }

        return $email;
    }

    protected function resolveUser(Student $student): User
    {
        $user = $student->user_id ? User::find($student->user_id) : null;

        if (! $user && filled($student->email)) {
            $user = User::where('email', $student->email)->first();
        }

        if (! $user) {
            $loginEmail = trim((string) $student->email);

            if ($loginEmail === '') {
                throw new \RuntimeException('Lo studente non ha un account di accesso: inserisci prima la sua email personale.');
            }

            $user = User::create([
                'name'     => $student->full_name !== '' ? $student->full_name : $loginEmail,
                'email'    => $loginEmail,
                'password' => Hash::make($this->generatePassword()),
            ]);
        }

        if (empty($student->user_id) && Schema::hasColumn('students', 'user_id')) {
            $student->forceFill(['user_id' => $user->id])->saveQuietly();
        }

        // Senza il ruolo "Studente" non si accede al portale studenti.
        if (Schema::hasTable('roles') && ! $user->hasRole('Studente') && Role::where('name', 'Studente')->exists()) {
            $user->assignRole('Studente');
        }

        return $user;
    }

    /**
     * Template attivo per l'evento; se nessuno è configurato usa quello
     * predefinito (non salvato), così funziona anche senza seeder/migrazioni.
     */
    protected function template(): EmailTemplate
    {
        return EmailTemplate::findByEvent(self::EVENT) ?? new EmailTemplate([
            'slug'          => 'student_credentials_resend',
            'name'          => 'Reinvio credenziali studente',
            'subject'       => 'Le tue nuove credenziali di accesso',
            'trigger_event' => self::EVENT,
            'is_active'     => true,
            'body_html'     => <<<'HTML'
<p>Ciao <strong>{{nome}}</strong>,</p>

<p>come richiesto, ti inviamo di nuovo le tue credenziali per accedere al portale. La password precedente non è più valida.</p>

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f5ff; border:1px solid #c3d4ef; border-radius:8px; margin:20px 0;">
  <tr><td style="padding:18px 20px;">
    <p style="margin:0 0 8px; font-weight:bold; color:#1e3a5f;">🔐 Le tue credenziali di accesso</p>
    <p style="margin:0 0 4px;"><strong>Portale:</strong> <a href="{{portale_url}}" style="color:#1e3a5f;">{{portale_url}}</a></p>
    <p style="margin:0 0 4px;"><strong>Email:</strong> {{email}}</p>
    <p style="margin:0 0 8px;"><strong>Password provvisoria:</strong> <span style="font-family:monospace; background:#e8eef8; padding:2px 8px; border-radius:4px;">{{password}}</span></p>
    <p style="margin:0; font-size:13px; color:#555;">Al primo accesso ti verrà chiesto di scegliere una nuova password.</p>
  </td></tr>
</table>

<p>Se non hai richiesto tu questo messaggio o hai bisogno di assistenza, rispondi a questa email o contatta la segreteria.</p>
HTML,
        ]);
    }

    /** Password temporanea leggibile (niente caratteri ambigui come 0/O, 1/l/I). */
    public function generatePassword(int $length = 12): string
    {
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghijkmnpqrstuvwxyz';
        $digits  = '23456789';
        $symbols = '!@#$%*?';

        $chars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        $all = $upper . $lower . $digits . $symbols;
        while (count($chars) < $length) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }
}
