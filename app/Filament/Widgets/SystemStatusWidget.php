<?php

namespace App\Filament\Widgets;

use App\Models\GoogleAccount;
use App\Models\NotificationEmailLog;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.system-status';
    protected static ?int   $sort = 0;                 // Prima di tutti gli altri widget
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '60s'; // Aggiorna ogni minuto

    public static function canView(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['superadmin', 'super_admin']) ?? false;
    }

    // ─── Dati esposti alla view ───────────────────────────────────────────────

    public function getStatusData(): array
    {
        return [
            'google'  => $this->googleStatus(),
            'email'   => $this->emailStatus(),
            'queue'   => $this->queueStatus(),
            'backup'  => $this->backupStatus(),
        ];
    }

    // ─── Google OAuth ─────────────────────────────────────────────────────────

    private function googleStatus(): array
    {
        $account = GoogleAccount::find(1);

        if (! $account || empty($account->access_token)) {
            return [
                'status'  => 'error',
                'label'   => 'Non collegato',
                'detail'  => 'Nessun account Google collegato. Vai in Impostazioni → Google per collegarlo.',
                'action_url' => route('filament.admin.pages.google-settings'),
                'action_label' => 'Collega ora',
            ];
        }

        if (empty($account->refresh_token)) {
            return [
                'status'  => 'warning',
                'label'   => 'Refresh token mancante',
                'detail'  => 'Il token di aggiornamento non è presente. Ricollega l\'account Google.',
                'action_url' => route('filament.admin.pages.google-settings'),
                'action_label' => 'Ricollega',
            ];
        }

        if ($account->isExpired()) {
            return [
                'status'  => 'warning',
                'label'   => 'Token scaduto',
                'detail'  => 'Il token è scaduto il ' . ($account->expires_at?->format('d/m/Y H:i') ?? '?')
                           . '. Il sistema proverà a rinnovarlo automaticamente alla prossima operazione Google.',
                'action_url' => route('filament.admin.pages.google-settings'),
                'action_label' => 'Verifica',
            ];
        }

        // Scade entro 5 minuti → warning preventivo
        if ($account->expires_at && $account->expires_at->diffInMinutes(now()) < 5 && $account->expires_at->isFuture()) {
            return [
                'status'  => 'warning',
                'label'   => 'Collegato (rinnovo imminente)',
                'detail'  => 'Account: ' . $account->email . ' — il token scade tra pochi minuti.',
                'action_url' => null,
                'action_label' => null,
            ];
        }

        return [
            'status'       => 'ok',
            'label'        => 'Collegato',
            'detail'       => 'Account: ' . $account->email
                            . ' — scade il ' . ($account->expires_at?->format('d/m/Y H:i') ?? 'N/D'),
            'action_url'   => null,
            'action_label' => null,
        ];
    }

    // ─── Ultima email inviata ─────────────────────────────────────────────────

    private function emailStatus(): array
    {
        $last = NotificationEmailLog::latest('sent_at')->first();

        if (! $last) {
            return [
                'status' => 'warning',
                'label'  => 'Nessuna email registrata',
                'detail' => 'Non è ancora stata inviata nessuna email tracciata dal sistema.',
            ];
        }

        $diff = $last->sent_at
            ? Carbon::parse($last->sent_at)->diffForHumans()
            : 'data sconosciuta';

        return [
            'status' => 'ok',
            'label'  => 'Ultima email: ' . $diff,
            'detail' => 'Tipo: ' . ($last->type ?? 'N/D')
                      . ' — a: ' . ($last->email ?? 'N/D'),
        ];
    }

    // ─── Stato backup ────────────────────────────────────────────────────────

    private function backupStatus(): array
    {
        try {
            $disk       = \Illuminate\Support\Facades\Storage::disk('local-backups');
            $backupName = config('backup.backup.name', config('app.name', 'ScuoleLive'));

            // I backup di spatie sono in una cartella col nome dell'app
            $files = collect($disk->allFiles($backupName))
                ->filter(fn ($f) => str_ends_with($f, '.zip'))
                ->sort()
                ->values();

            if ($files->isEmpty()) {
                return [
                    'status' => 'error',
                    'label'  => 'Nessun backup trovato',
                    'detail' => 'Nessun file .zip trovato in storage/app/backups. Eseguire: php artisan backup:run',
                ];
            }

            $lastFile    = $files->last();
            $lastModified = Carbon::createFromTimestamp($disk->lastModified($lastFile));
            $age          = $lastModified->diffInHours(now());
            $sizeBytes    = $disk->size($lastFile);
            $sizeMb       = round($sizeBytes / 1024 / 1024, 1);
            $label        = 'Ultimo: ' . $lastModified->format('d/m/Y H:i')
                          . ' (' . round($age, 1) . 'h fa, ' . $sizeMb . ' MB)';

            // ── Valutazione età backup ────────────────────────────────────────
            // Backup giornaliero schedulato alle 02:00 → in giornata l'età è < 26h.
            // Oltre 48h è considerato error (saltato un giorno o più).
            // Tra 26h e 48h è warning (deploy ritardato, cron mancato, ecc.).
            if ($age > 48) {
                return [
                    'status' => 'error',
                    'label'  => $label,
                    'detail' => 'Ultimo backup oltre 48 ore fa. Verificare il cron schedule:run e il comando backup:run.',
                ];
            }

            if ($age > 26) {
                return [
                    'status' => 'warning',
                    'label'  => $label,
                    'detail' => 'Ultimo backup oltre 26 ore fa. Lo schedule giornaliero potrebbe non essere stato eseguito.',
                ];
            }

            return [
                'status' => 'ok',
                'label'  => $label,
                'detail' => 'Backup recente disponibile in storage/app/' . $backupName . '.',
            ];
        } catch (\Throwable $e) {
            report($e);
            return [
                'status' => 'error',
                'label'  => 'Errore lettura backup',
                'detail' => 'Eccezione durante l\'accesso al disco backup: ' . $e->getMessage(),
            ];
        }
    }

    // ─── Stato code (queue) ──────────────────────────────────────────────────

    private function queueStatus(): array
    {
        try {
            // Conta job in coda (solo per driver "database")
            $pending = 0;
            $failed  = 0;

            if (DB::getSchemaBuilder()->hasTable('jobs')) {
                $pending = (int) DB::table('jobs')->count();
            }
            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                $failed = (int) DB::table('failed_jobs')->count();
            }

            // Se ci sono failed jobs è sempre warning/error
            if ($failed >= 10) {
                return [
                    'status' => 'error',
                    'label'  => "{$failed} job falliti, {$pending} in coda",
                    'detail' => 'Più di 10 job falliti. Esegui: php artisan queue:retry all (oppure queue:flush per scartarli).',
                ];
            }

            if ($failed > 0) {
                return [
                    'status' => 'warning',
                    'label'  => "{$failed} job falliti, {$pending} in coda",
                    'detail' => 'Sono presenti job falliti. Verifica in Failed Jobs e considera retry o flush.',
                ];
            }

            // Coda piena ma senza fallimenti = il worker è giù o lento
            if ($pending >= 50) {
                return [
                    'status' => 'warning',
                    'label'  => "{$pending} job in coda",
                    'detail' => 'Coda lunga: verifica che il worker (queue:work) sia attivo.',
                ];
            }

            return [
                'status' => 'ok',
                'label'  => $pending === 0 ? 'Coda vuota' : "{$pending} job in coda",
                'detail' => 'Worker operativo, nessun job fallito.',
            ];
        } catch (\Throwable $e) {
            report($e);
            return [
                'status' => 'error',
                'label'  => 'Errore lettura coda',
                'detail' => 'Eccezione durante la lettura tabella jobs: ' . $e->getMessage(),
            ];
        }
    }
}
