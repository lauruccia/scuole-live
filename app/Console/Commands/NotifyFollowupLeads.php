<?php

namespace App\Console\Commands;

use App\Models\Lead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifyFollowupLeads extends Command
{
    protected $signature   = 'crm:notify-followup';
    protected $description = 'Invia email agli utenti assegnati per i lead con follow-up in scadenza oggi o scaduti.';

    public function handle(): int
    {
        $leads = Lead::with(['assignedTo', 'course'])
            ->whereNotNull('followup_at')
            ->whereNotIn('status', ['enrolled', 'lost'])
            ->where(function ($q) {
                $q->whereDate('followup_at', today())
                  ->orWhereDate('followup_at', '<', today());
            })
            ->get();

        if ($leads->isEmpty()) {
            $this->info('Nessun follow-up in scadenza.');
            return self::SUCCESS;
        }

        // Raggruppa per utente assegnato
        $byUser = $leads->groupBy('assigned_to');

        foreach ($byUser as $userId => $userLeads) {
            $user = $userLeads->first()->assignedTo;
            if (! $user || ! $user->email) continue;

            Mail::send([], [], function ($message) use ($user, $userLeads) {
                $message
                    ->to($user->email, $user->name)
                    ->subject('📋 Follow-up lead in scadenza — ' . today()->format('d/m/Y'))
                    ->html($this->buildEmailHtml($userLeads));
            });

            $this->info("Email inviata a {$user->email} ({$userLeads->count()} lead)");
        }

        return self::SUCCESS;
    }

    private function buildEmailHtml($leads): string
    {
        $rows = '';
        foreach ($leads as $lead) {
            $date     = $lead->followup_at?->format('d/m/Y') ?? '-';
            $isLate   = $lead->hasOverdueFollowup();
            $flag     = $isLate ? ' ⚠️ scaduto' : ' — oggi';
            $status   = \App\Models\Lead::STATUSES[$lead->status] ?? $lead->status;
            $course   = $lead->course?->name ?? '-';

            $rows .= "<tr>
                <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:600;'>
                    {$lead->full_name}
                </td>
                <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;'>{$course}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#6b7280;'>{$status}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;color:" . ($isLate ? '#ef4444' : '#f59e0b') . ";font-weight:600;'>
                    {$date}{$flag}
                </td>
            </tr>";
        }

        return "
        <html><body style='font-family:system-ui,sans-serif;color:#111;'>
        <h2 style='color:#1d4ed8;'>Follow-up lead in scadenza</h2>
        <p style='color:#6b7280;'>Hai {$leads->count()} lead con follow-up da gestire:</p>
        <table style='width:100%;border-collapse:collapse;margin-top:16px;'>
            <thead>
                <tr style='background:#f3f4f6;'>
                    <th style='padding:8px 12px;text-align:left;'>Lead</th>
                    <th style='padding:8px 12px;text-align:left;'>Corso</th>
                    <th style='padding:8px 12px;text-align:left;'>Stato</th>
                    <th style='padding:8px 12px;text-align:left;'>Follow-up</th>
                </tr>
            </thead>
            <tbody>{$rows}</tbody>
        </table>
        <p style='margin-top:24px;font-size:12px;color:#9ca3af;'>
            Inviato automaticamente da ScuoleLive — " . now()->format('d/m/Y H:i') . "
        </p>
        </body></html>";
    }
}
