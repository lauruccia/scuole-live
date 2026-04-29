<?php

namespace App\Filament\Widgets;

use App\Models\GoogleAccount;
use App\Models\NotificationEmailLog;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
            'google'     => $this->googleStatus(),
            'email'      => $this->emailStatus(),
            'queue'      => $this->queueStatus(),
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

    // ─── Code in attesa ───────────────────────────────────────────────────────

    private function queueStatus(): array
    {
        // Conta job in coda e job falliti
        try {
            $pending = DB::table('jobs')->count();
            $failed  = DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            return [
                'status' => 'warning',
                'label'  => 'Tabella jobs non trovata',
                'detail' => 'Assicurarsi di aver eseguito php artisan migrate.',
            ];
        }

        if ($failed > 0) {
            return [
                'status' => 'error',
                'label'  => $pending . ' in coda — ' . $failed . ' falliti',
                'detail' => 'Ci sono ' . $failed . ' job falliti. Eseguire: php artisan queue:retry all',
            ];
        }

        if ($pending > 10) {
            return [
                'status' => 'warning',
                'label'  => $pending . ' job in attesa',
                'detail' => 'La coda ha un backlog elevato. Verificare che il worker sia in esecuzione.',
            ];
        }

        return [
            'status' => 'ok',
            'label'  => $pending === 0 ? 'Coda vuota' : $pending . ' job in coda',
            'detail' => $failed === 0 ? 'Nessun job fallito.' : '',
        ];
    }
}
