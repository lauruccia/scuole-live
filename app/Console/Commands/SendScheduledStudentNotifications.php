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
 * Invia notifiche automatiche per:
 *   1. Rate in scadenza tra 5 giorni  (tipo: installment_due_5_days)
 *   2. Fine corso tra 20 giorni       (tipo: course_end_20_days)
 *
 * Routing email (per entrambe le tipologie):
 *  - To:  intestatario del contratto (billing_email) se valido,
 *         altrimenti primo studente con email valida.
 *  - CC:  tutti gli studenti con email valida diversa dal To.
 *
 * Un'unica email per contratto — gli studenti in CC ricevono copia senza
 * generare email duplicate in caso di contratti con piu beneficiari.
 */
class SendScheduledStudentNotifications extends Command
{
    protected $signature   = 'school:send-student-notifications
                              {--dry-run : Mostra cosa verrebbe inviato senza inviare email}';

    protected $description = 'Invia notifiche automatiche per rate in scadenza e fine corso.';

    public function __construct(private readonly EmailTemplateService $emailService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Invio notifiche studenti avviato...');

        $dryRun  = $this->option('dry-run');
        $today   = Carbon::today();
        $sent    = 0;
        $skipped = 0;
        $errors  = 0;

        if ($dryRun) {
            $this->warn('-- DRY RUN: nessuna email verra inviata --');
        }

        // 1. Rate in scadenza tra 5 giorni
        $targetInstallment = $today->copy()->addDays(5)->toDateString();

        $installments = Installment::query()
            ->with(['contract.students'])
            ->whereDate('due_date', $targetInstallment)
            ->whereNull('paid_at')
            ->whereNull('deleted_at')
            ->get();

        foreach ($installments as $installment) {
            $result = $this->processInstallmentNotification($installment, $dryRun);
            match ($result) {
                'sent'    => $sent++,
                'skipped' => $skipped++,
                default   => $errors++,
            };
        }

        // 2. Fine corso tra 20 giorni
        $targetEnd = $today->copy()->addDays(20)->toDateString();

        $contracts = Contract::query()
            ->with(['students', 'course'])
            ->whereDate('ends_at', $targetEnd)
            ->whereNull('deleted_at')
            ->get();

        foreach ($contracts as $contract) {
            $result = $this->processCourseEndNotification($contract, $dryRun);
            match ($result) {
                'sent'    => $sent++,
                'skipped' => $skipped++,
                default   => $errors++,
            };
        }

        $this->info("Completato — Inviate: {$sent} | Gia inviate (skip): {$skipped} | Errori: {$errors}");

        if ($errors > 0) {
            Log::warning("school:send-student-notifications — {$errors} errori durante l'invio.");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    // ─── Handlers privati ─────────────────────────────────────────────────────

    /**
     * Invia promemoria rata in scadenza per un singolo installment.
     * Una sola email per contratto (intestatario To + studenti CC).
     */
    private function processInstallmentNotification(Installment $installment, bool $dryRun): string
    {
        $contract = $installment->contract;

        if (! $contract) {
            return 'error';
        }

        // Deduplicazione per contratto + installment (non per singolo studente)
        $alreadySent = NotificationEmailLog::query()
            ->where('type', 'installment_due_5_days')
            ->where('contract_id', $contract->id)
            ->where('installment_id', $installment->id)
            ->exists();

        if ($alreadySent) {
            $this->line("  SKIP  Rata #{$installment->id} contratto #{$contract->id} — gia inviato.");
            return 'skipped';
        }

        ['to' => $to, 'cc' => $cc] = $contract->contractNotificationRecipients();

        if (! $to) {
            $this->warn("  ERR   Rata #{$installment->id} — nessun indirizzo email valido.");
            return 'error';
        }

        $ccLabel = ! empty($cc)
            ? ' (CC: ' . collect($cc)->pluck('email')->implode(', ') . ')'
            : '';
        $this->line('  ' . ($dryRun ? 'DRY   ' : 'SEND  ') . "Rata #{$installment->id} -> {$to['email']}{$ccLabel}");

        if ($dryRun) {
            return 'sent';
        }

        $variables = [
            'nome_intestatario' => $to['name'],
            'numero_rata'       => (string) ($installment->number ?? $installment->id),
            'data_scadenza'     => Carbon::parse($installment->due_date)->format('d/m/Y'),
            'importo'           => number_format((float) ($installment->amount ?? 0), 2, ',', '.'),
            'nome_scuola'       => SchoolSetting::schoolName(),
        ];

        $ok = $this->emailService->sendBySlug(
            'installment_overdue',
            $to['email'],
            $to['name'],
            $variables,
            [],
            $cc
        );

        if ($ok) {
            NotificationEmailLog::create([
                'type'           => 'installment_due_5_days',
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

    /**
     * Invia promemoria fine corso per un singolo contratto.
     * Una sola email per contratto (intestatario To + studenti CC).
     */
    private function processCourseEndNotification(Contract $contract, bool $dryRun): string
    {
        // Deduplicazione per contratto
        $alreadySent = NotificationEmailLog::query()
            ->where('type', 'course_end_20_days')
            ->where('contract_id', $contract->id)
            ->exists();

        if ($alreadySent) {
            $this->line("  SKIP  Contratto #{$contract->id} fine corso — gia inviato.");
            return 'skipped';
        }

        ['to' => $to, 'cc' => $cc] = $contract->contractNotificationRecipients();

        if (! $to) {
            $this->warn("  ERR   Contratto #{$contract->id} — nessun indirizzo email valido.");
            return 'error';
        }

        $ccLabel = ! empty($cc)
            ? ' (CC: ' . collect($cc)->pluck('email')->implode(', ') . ')'
            : '';
        $this->line('  ' . ($dryRun ? 'DRY   ' : 'SEND  ') . "Contratto #{$contract->id} fine corso -> {$to['email']}{$ccLabel}");

        if ($dryRun) {
            return 'sent';
        }

        $courseName = $contract->course?->name ?? 'corso';

        $variables = [
            'nome_intestatario' => $to['name'],
            'nome_corso'        => $courseName,
            'data_fine_corso'   => $contract->ends_at
                ? Carbon::parse($contract->ends_at)->format('d/m/Y')
                : '-',
            'nome_scuola'       => SchoolSetting::schoolName(),
        ];

        $ok = $this->emailService->sendBySlug(
            'course_end_reminder',
            $to['email'],
            $to['name'],
            $variables,
            [],
            $cc
        );

        if ($ok) {
            NotificationEmailLog::create([
                'type'           => 'course_end_20_days',
                'installment_id' => null,
                'contract_id'    => $contract->id,
                'student_id'     => null,
                'reference_date' => $contract->ends_at,
                'email'          => $to['email'],
                'sent_at'        => now(),
            ]);
            return 'sent';
        }

        $this->warn("  ERR   Contratto #{$contract->id} — invio email fine corso fallito.");
        return 'error';
    }
}
