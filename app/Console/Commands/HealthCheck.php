<?php

namespace App\Console\Commands;

use App\Models\GoogleAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Audit pre-deploy / pre-lancio del sistema scuoleLive.
 *
 * Verifica:
 *  - Connessione DB + tabelle critiche presenti
 *  - APP_KEY, APP_DEBUG, APP_ENV
 *  - Configurazione Mail
 *  - Configurazione Stripe / PayPal / Google
 *  - Configurazione Backup (disco, retention)
 *  - Permessi storage scrivibili
 *  - Schedule Laravel popolato
 *
 * Uso:
 *   php artisan school:health-check
 *   php artisan school:health-check --strict   # exit code 1 se WARN o FAIL
 */
class HealthCheck extends Command
{
    protected $signature   = 'school:health-check {--strict : Exit code != 0 anche su warning}';
    protected $description = 'Audit di salute del sistema (config, DB, mail, gateway, backup)';

    private array $results = [];

    public function handle(): int
    {
        $this->info('🔍 ScuoleLive — Health Check');
        $this->newLine();

        $this->checkAppEnv();
        $this->checkDatabase();
        $this->checkStorage();
        $this->checkMailConfig();
        $this->checkStripeConfig();
        $this->checkPayPalConfig();
        $this->checkGoogleConfig();
        $this->checkBackupConfig();
        $this->checkSchedule();

        $this->newLine();
        $this->printSummary();

        $hasFail = collect($this->results)->contains(fn ($r) => $r['level'] === 'FAIL');
        $hasWarn = collect($this->results)->contains(fn ($r) => $r['level'] === 'WARN');

        if ($hasFail) return 2;
        if ($hasWarn && $this->option('strict')) return 1;
        return 0;
    }

    private function record(string $level, string $area, string $message): void
    {
        $this->results[] = compact('level', 'area', 'message');
        $icon = match ($level) {
            'OK'   => '✅',
            'WARN' => '⚠️ ',
            'FAIL' => '❌',
            default => '•',
        };
        $this->line("  {$icon} [{$area}] {$message}");
    }

    private function checkAppEnv(): void
    {
        $this->info('— App / Env —');
        $env = config('app.env');
        $debug = config('app.debug');
        $key = config('app.key');

        if (empty($key)) {
            $this->record('FAIL', 'app', 'APP_KEY non configurato. Esegui: php artisan key:generate');
        } else {
            $this->record('OK', 'app', "APP_KEY presente ({$env})");
        }

        if ($env === 'production' && $debug) {
            $this->record('FAIL', 'app', 'APP_DEBUG=true in production — RISCHIO SICUREZZA');
        } elseif ($debug) {
            $this->record('WARN', 'app', "APP_DEBUG=true (env={$env})");
        } else {
            $this->record('OK', 'app', 'APP_DEBUG=false');
        }

        $tz = config('app.timezone');
        if ($tz === 'Europe/Rome') {
            $this->record('OK', 'app', "Timezone {$tz}");
        } else {
            $this->record('WARN', 'app', "Timezone {$tz} (atteso Europe/Rome)");
        }
    }

    private function checkDatabase(): void
    {
        $this->info('— Database —');
        try {
            DB::connection()->getPdo();
            $name = DB::connection()->getDatabaseName();
            $this->record('OK', 'db', "Connesso a {$name}");
        } catch (\Throwable $e) {
            $this->record('FAIL', 'db', 'Connessione DB fallita: ' . $e->getMessage());
            return;
        }

        $criticalTables = ['users', 'students', 'contracts', 'lessons', 'course_purchases', 'jobs', 'failed_jobs', 'sessions'];
        foreach ($criticalTables as $t) {
            if (Schema::hasTable($t)) {
                $this->record('OK', 'db', "Tabella `{$t}` presente");
            } else {
                $this->record('FAIL', 'db', "Tabella `{$t}` MANCANTE");
            }
        }

        if (Schema::hasTable('student_unsubscribes')) {
            $this->record('OK', 'db', 'Tabella `student_unsubscribes` presente (GDPR)');
        } else {
            $this->record('WARN', 'db', 'Tabella `student_unsubscribes` mancante (no GDPR unsubscribe)');
        }
    }

    private function checkStorage(): void
    {
        $this->info('— Storage —');
        $paths = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('app/public'),
        ];
        foreach ($paths as $p) {
            if (! is_dir($p)) {
                $this->record('FAIL', 'storage', "{$p} non esiste");
            } elseif (! is_writable($p)) {
                $this->record('FAIL', 'storage', "{$p} non scrivibile (chmod 775)");
            } else {
                $this->record('OK', 'storage', "{$p} scrivibile");
            }
        }
    }

    private function checkMailConfig(): void
    {
        $this->info('— Mail —');
        $mailer = config('mail.default');
        $host   = config('mail.mailers.' . $mailer . '.host');
        $from   = config('mail.from.address');
        if ($mailer === 'log' || $mailer === 'array') {
            $this->record('WARN', 'mail', "MAIL_MAILER={$mailer} (NO invio reale)");
        } elseif (empty($host)) {
            $this->record('FAIL', 'mail', "MAIL_HOST non configurato per {$mailer}");
        } else {
            $this->record('OK', 'mail', "Mailer {$mailer} → {$host}");
        }
        if (empty($from)) {
            $this->record('FAIL', 'mail', 'MAIL_FROM_ADDRESS non configurato');
        } else {
            $this->record('OK', 'mail', "From: {$from}");
        }
    }

    private function checkStripeConfig(): void
    {
        $this->info('— Stripe —');
        $this->checkConfigKey('services.stripe.key',            'stripe', 'STRIPE_KEY');
        $this->checkConfigKey('services.stripe.secret',         'stripe', 'STRIPE_SECRET');
        $this->checkConfigKey('services.stripe.webhook_secret', 'stripe', 'STRIPE_WEBHOOK_SECRET');
    }

    private function checkPayPalConfig(): void
    {
        $this->info('— PayPal —');
        $this->checkConfigKey('services.paypal.client_id', 'paypal', 'PAYPAL_CLIENT_ID');
        $this->checkConfigKey('services.paypal.secret',    'paypal', 'PAYPAL_SECRET');
        $this->checkConfigKey('services.paypal.base_url',  'paypal', 'PAYPAL_BASE_URL');
        $wid = config('services.paypal.webhook_id');
        if (empty($wid) && app()->environment('production')) {
            $this->record('FAIL', 'paypal', 'PAYPAL_WEBHOOK_ID non configurato in production (webhook BLOCCATI)');
        } elseif (empty($wid)) {
            $this->record('WARN', 'paypal', 'PAYPAL_WEBHOOK_ID non configurato (verifica firma saltata)');
        } else {
            $this->record('OK', 'paypal', 'PAYPAL_WEBHOOK_ID configurato');
        }
    }

    private function checkGoogleConfig(): void
    {
        $this->info('— Google Calendar/Meet —');
        $this->checkConfigKey('services.google.calendar_id', 'google', 'GOOGLE_CALENDAR_ID');
        $sa = config('services.google.service_account_json');
        if (empty($sa)) {
            $this->record('WARN', 'google', 'GOOGLE_SERVICE_ACCOUNT_JSON non configurato');
        } elseif (! file_exists(base_path($sa)) && ! file_exists($sa)) {
            $this->record('FAIL', 'google', "Service account JSON non trovato: {$sa}");
        } else {
            $this->record('OK', 'google', "Service account JSON presente: {$sa}");
        }
        try {
            $acc = GoogleAccount::find(1);
            if ($acc && $acc->access_token) {
                $exp = $acc->expires_at?->isFuture() ? 'valido' : 'scaduto (rinnovo automatico al prossimo uso)';
                $this->record('OK', 'google', "Account collegato — token {$exp}");
            } else {
                $this->record('WARN', 'google', 'Nessun GoogleAccount collegato (id=1)');
            }
        } catch (\Throwable $e) {
            $this->record('WARN', 'google', 'Lettura GoogleAccount fallita: ' . $e->getMessage());
        }
    }

    private function checkBackupConfig(): void
    {
        $this->info('— Backup —');
        $disks = config('backup.backup.destination.disks', []);
        if (empty($disks)) {
            $this->record('FAIL', 'backup', 'Nessun disco configurato in backup.destination.disks');
            return;
        }
        $this->record('OK', 'backup', 'Disco configurato: ' . implode(', ', $disks));

        $email = config('backup.notifications.mail.to');
        if (empty($email)) {
            $this->record('WARN', 'backup', 'BACKUP_NOTIFICATION_EMAIL non configurato');
        } else {
            $this->record('OK', 'backup', "Notifiche backup → {$email}");
        }

        // Verifica esistenza ultima copia
        try {
            $disk = Storage::disk($disks[0]);
            $name = config('backup.backup.name', 'ScuoleLive');
            $files = collect($disk->allFiles($name))->filter(fn ($f) => str_ends_with($f, '.zip'));
            if ($files->isEmpty()) {
                $this->record('WARN', 'backup', "Nessun .zip trovato in {$disks[0]}/{$name} (esegui backup:run)");
            } else {
                $this->record('OK', 'backup', $files->count() . ' backup presenti');
            }
        } catch (\Throwable $e) {
            $this->record('WARN', 'backup', 'Lettura disco backup fallita: ' . $e->getMessage());
        }
    }

    private function checkSchedule(): void
    {
        $this->info('— Schedule Laravel —');
        // Conta entry: l'oggetto Schedule è singleton in app
        $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();
        $count = count($events);
        if ($count === 0) {
            $this->record('FAIL', 'schedule', 'Nessuna entry Schedule:: trovata in routes/console.php');
        } elseif ($count < 5) {
            $this->record('WARN', 'schedule', "Solo {$count} entry Schedule:: (atteso almeno 7-9)");
        } else {
            $this->record('OK', 'schedule', "{$count} entry pianificate");
        }
    }

    private function checkConfigKey(string $configKey, string $area, string $envName): void
    {
        $val = config($configKey);
        if (empty($val)) {
            $this->record('FAIL', $area, "{$envName} non configurato");
        } else {
            $masked = is_string($val) ? substr($val, 0, 6) . '…' : '[set]';
            $this->record('OK', $area, "{$envName} configurato ({$masked})");
        }
    }

    private function printSummary(): void
    {
        $ok   = collect($this->results)->where('level', 'OK')->count();
        $warn = collect($this->results)->where('level', 'WARN')->count();
        $fail = collect($this->results)->where('level', 'FAIL')->count();
        $this->newLine();
        $this->line('═══════════════════════════════════════════════');
        $this->line("  ✅ OK: {$ok}    ⚠️  WARN: {$warn}    ❌ FAIL: {$fail}");
        $this->line('═══════════════════════════════════════════════');
        if ($fail > 0) {
            $this->error('Audit FAILED — risolvere i FAIL prima del go-live');
        } elseif ($warn > 0) {
            $this->warn('Audit con warning — leggere ed eventualmente risolvere');
        } else {
            $this->info('🎉 Tutti i controlli sono passati');
        }
    }
}
