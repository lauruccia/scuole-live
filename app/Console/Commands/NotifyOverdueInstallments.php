<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Installment;
use App\Models\NotificationEmailLog;
use App\Models\SchoolSetting;
use App\Services\EmailTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Invia promemoria per le rate non pagate imminenti o già scadute.
 *
 * Comportamento:
 *  - Abilitato/disabilitato via SchoolSetting 'reminder_installment_enabled'
 *  - Anticipo configurabile via SchoolSetting 'reminder_installment_days_before' (default 3)
 *  - Invia UN promemoria per evento per rata (deduplicato via NotificationEmailLog)
 *  - Due tipologie:
 *      · installment.upcoming — rata in scadenza tra X giorni
 *      · installment.overdue  — rata già scaduta (inviato il giorno stesso della scadenza)
 */
class NotifyOverdueInstallments extends Command
{
    protected $signature   = 'installments:notify-overdue
                              {--dry-run : Mostra cosa verrebbe inviato senza inviare email}';

    protected $description = 'Invia promemoria email per le rate non pagate in scadenza o già scadute.';

    public function __construct(private readonly EmailTemplateService $emailService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! SchoolSetting::bool('reminder_installment_enabled', false)) {
            $this->info('Promemoria rate disabilitato nelle impostazioni scuola. Nessuna email inviata.');
            return self::SUCCESS;
        }

        $daysBefore = (int) SchoolSetting::get('reminder_installment_days_before', 3);
        $today      = Carbon::today();
        $dryRun     = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('-- DRY RUN: nessuna email verrà inviata --');
        }

        $sent    = 0;
        $skipped = 0;
        $errors  = 0;

        // ── Rata in scadenza tra $daysBefore giorni ────────────────────────────
        $upcoming = $this->getUnpaidInstallments(
            from: $today->copy()->addDays($daysBefore),
            to:   $today->copy()->addDays($daysBefore),
        );

        foreach ($upcoming as $installment) {
            $result = $this->processInstallment(
                installment: $installment,
                type:        'installment.upcoming',
                dryRun:      $dryRun,
            );
            match ($result) {
                'sent'    => $sent++,
                'skipped' => $skipped++,
                default   => $errors++,
            };
        }

        // ── Rata scaduta oggi (due_date = ieri → scaduta) ─────────────────────
        // Inviamo il giorno della scadenza (not paid e due_date = today)
        // così il destinatario riceve una notifica il giorno stesso.
        $dueToday = $this->getUnpaidInstallments(from: $today, to: $today);

        foreach ($dueToday as $installment) {
            $result = $this->processInstallment(
                installment: $installment,
                type:        'installment.overdue',
                dryRun:      $dryRun,
            );
            match ($result) {
                'sent'    => $sent++,
                'skipped' => $skipped++,
                default   => $errors++,
            };
        }

        $this->info("Completato — Inviate: {$sent} | Già inviate (skip): {$skipped} | Errori: {$errors}");

        if ($errors > 0) {
            Log::warning("installments:notify-overdue — {$errors} errori durante l'invio promemoria.");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Restituisce le rate non pagate con due_date nell'intervallo [from, to].
     * Eager-load contract.students per evitare N+1 nel routing notifiche.
     */
    private function getUnpaidInstallments(Carbon $from, Carbon $to)
    {
        return Installment::query()
            ->whereNull('paid_at')
            ->whereNull('deleted_at')
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->with(['contract.students'])
            ->get();
    }

    /**
     * Processa un singolo installment: deduplicazione + invio.
     * Restituisce 'sent' | 'skipped' | 'error'.
     *
     * Routing:
     *  - To:  intestatario (billing_email) se valido, altrimenti primo studente con email.
     *  - CC:  tutti gli studenti con email valida ≠ To.
     */
    private function processInstallment(Installment $installment, string $type, bool $dryRun): string
    {
        // Deduplicazione: non inviare due volte lo stesso tipo per la stessa rata
        $alreadySent = NotificationEmailLog::where('installment_id', $installment->id)
            ->where('type', $type)
            ->exists();

        if ($alreadySent) {
            $this->line("  SKIP  Rata #{$installment->id} (tipo: {$type}) — già inviato.");
            return 'skipped';
        }

        $contract = $installment->contract;

        if (! $contract) {
            $this->warn("  ERR   Rata #{$installment->id} — contratto non trovato.");
            return 'error';
        }

        ['to' => $to, 'cc' => $cc] = $contract->contractNotificationRecipients();

        if (! $to) {
            $this->warn("  ERR   Rata #{$installment->id} — nessun indirizzo email valido (intestatario e studenti).");
            return 'error';
        }

        $variables = [
            'nome_intestatario' => $to['name'],
            'numero_rata'       => (string) ($installment->number ?? $installment->id),
            'data_scadenza'     => Carbon::parse($installment->due_date)->format('d/m/Y'),
            'importo'           => number_format((float) ($installment->amount ?? 0), 2, ',', '.'),
            'nome_scuola'       => SchoolSetting::schoolName(),
        ];

        $ccLabel = ! empty($cc) ? ' (CC: ' . collect($cc)->pluck('email')->implode(', ') . ')' : '';
        $this->line("  " . ($dryRun ? 'DRY   ' : 'SEND  ') . "Rata #{$installment->id} → {$to['email']}{$ccLabel} (tipo: {$type})");

        if ($dryRun) {
            return 'sent';
        }

        // Usiamo sendBySlug con template unico 'installment_overdue'.
        // Il $type distingue solo a scopo di deduplicazione nel log.
        $ok = $this->emailService->sendBySlug('installment_overdue', $to['email'], $to['name'], $variables, [], $cc);

        if ($ok) {
            NotificationEmailLog::create([
                'type'           => $type,
                'installment_id' => $installment->id,
                'contract_id'    => $contract->id,
                'student_id'     => null,
                'reference_date' => $installment->due_date,
                'email'          => $to['email'],
                'sent_at'        => now(),
            ]);
            return 'sent';
        }

        $this->warn("  ERR   Rata #{$installment->id} — invio email fallito.");
        return 'error';
    }
}
