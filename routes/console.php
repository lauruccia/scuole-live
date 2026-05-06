<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Schedule
|--------------------------------------------------------------------------
| Comandi artisan custom + scheduling.
|
| ATTENZIONE: in produzione il cron cPanel deve eseguire ogni minuto:
|   * * * * * /usr/local/bin/php /home/aeacenter/scuole_app/artisan schedule:run >> /dev/null 2>&1
|
| IMPORTANTE: usare /usr/local/bin/php (CLI) e NON /usr/bin/php (CGI) su Aruba.
| /usr/bin/php su Aruba e' la versione CGI: ignora gli argomenti e stampa l'help
| di artisan invece di eseguire i comandi (causa #2 dell'incident 2026-05-06).
|
| Senza questo cron NESSUN comando schedulato qui sotto verrà eseguito.
*/

// ── Comando "inspire" Laravel default ────────────────────────────────────────
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─────────────────────────────────────────────────────────────────────────────
// SCHEDULE
// ─────────────────────────────────────────────────────────────────────────────

// ── CRM ──────────────────────────────────────────────────────────────────────
// Notifica follow-up lead non contattati: ogni giorno alle 08:00
Schedule::command('crm:notify-followup')
    ->dailyAt('08:00')
    ->timezone('Europe/Rome')
    ->withoutOverlapping(10)
    ->onOneServer()
    ->runInBackground();

// ── Promemoria rate scadute ──────────────────────────────────────────────────
// Invia promemoria agli studenti con rate in scadenza/scadute: ogni giorno alle 09:00
// Il toggle on/off è in ImpostazioniScuola; il comando rispetta quel flag.
Schedule::command('installments:notify-overdue')
    ->dailyAt('09:00')
    ->timezone('Europe/Rome')
    ->withoutOverlapping(10)
    ->onOneServer()
    ->runInBackground();

// ── Notifiche programmate agli studenti ──────────────────────────────────────
// Invia le notifiche schedulate dalla Segreteria: ogni 5 minuti
Schedule::command('school:send-student-notifications')
    ->everyFiveMinutes()
    ->timezone('Europe/Rome')
    ->withoutOverlapping(5)
    ->onOneServer()
    ->runInBackground();

// ── Riconciliazione conteggi lezioni future ──────────────────────────────────
// Job di manutenzione notturna: ricalcola i conteggi "lezioni residue/consumate"
// sui contratti dove i contatori sono fuori sync. Eseguito ogni giorno alle 03:30.
Schedule::command('lessons:fix-future-counts')
    ->dailyAt('03:30')
    ->timezone('Europe/Rome')
    ->withoutOverlapping(10)
    ->onOneServer()
    ->runInBackground();

// ── Backup database (giornaliero) ────────────────────────────────────────────
// spatie/laravel-backup: dump del solo DB ogni notte alle 02:00
Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->timezone('Europe/Rome')
    ->withoutOverlapping(60)
    ->onOneServer()
    ->runInBackground();

// ── Backup completo (settimanale) ────────────────────────────────────────────
// Backup full (codice + storage + DB) ogni domenica alle 03:00
Schedule::command('backup:run')
    ->weeklyOn(0, '03:00') // 0 = domenica
    ->timezone('Europe/Rome')
    ->withoutOverlapping(120)
    ->onOneServer()
    ->runInBackground();

// ── Pulizia retention backup ─────────────────────────────────────────────────
// Cancella backup vecchi secondo la policy in config/backup.php
Schedule::command('backup:clean')
    ->dailyAt('01:30')
    ->timezone('Europe/Rome')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->runInBackground();

// ── Monitor backup ───────────────────────────────────────────────────────────
// Notifica via email a BACKUP_NOTIFICATION_EMAIL se l'ultimo backup è troppo vecchio
Schedule::command('backup:monitor')
    ->dailyAt('08:30')
    ->timezone('Europe/Rome')
    ->onOneServer()
    ->runInBackground();

// ── Pulizia activity log ─────────────────────────────────────────────────────
// Rimuove i record di activity log più vecchi di X giorni
// (config: config/activitylog.php → delete_records_older_than_days)
// Eseguito il primo del mese alle 04:00
Schedule::command('activitylog:clean')
    ->monthlyOn(1, '04:00')
    ->timezone('Europe/Rome')
    ->onOneServer()
    ->runInBackground();

// ── Pulizia sessioni / token / cache scaduti ────────────────────────────────
// Rimuove token di reset password scaduti, cache stale ecc.
Schedule::command('auth:clear-resets')
    ->daily()
    ->timezone('Europe/Rome')
    ->onOneServer();
