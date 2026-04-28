<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\LeadActivity;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class LeadKanban extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-view-columns';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Pipeline Kanban';
    protected static ?string $title           = 'Pipeline CRM';
    protected static ?int    $navigationSort  = 2;

    protected static string $view = 'filament.pages.lead-kanban';

    /** Colonne della pipeline nell'ordine desiderato */
    public function getColumns(): array
    {
        return [
            'new'           => ['label' => 'Nuovo',           'color' => '#6b7280', 'bg' => '#f3f4f6'],
            'contacted'     => ['label' => 'Contattato',      'color' => '#3b82f6', 'bg' => '#eff6ff'],
            'proposal_sent' => ['label' => 'Proposta inviata','color' => '#f59e0b', 'bg' => '#fffbeb'],
            'enrolled'      => ['label' => 'Iscritto',        'color' => '#22c55e', 'bg' => '#f0fdf4'],
            'lost'          => ['label' => 'Perso',           'color' => '#ef4444', 'bg' => '#fff5f5'],
        ];
    }

    /** Lead raggruppati per stato (solo attivi + persi recenti) */
    public function getLeadsByStatus(): array
    {
        $leads = Lead::with(['course', 'assignedTo'])
            ->orderBy('followup_at')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('status');

        $result = [];
        foreach (array_keys($this->getColumns()) as $status) {
            $result[$status] = $leads->get($status, collect());
        }
        return $result;
    }

    /** Sposta un lead in un nuovo stato (chiamato via Livewire wire:click) */
    public function moveToStatus(int $leadId, string $newStatus): void
    {
        $lead = Lead::findOrFail($leadId);
        $oldStatus = $lead->status;

        if ($oldStatus === $newStatus) return;

        $lead->update(['status' => $newStatus]);

        LeadActivity::create([
            'lead_id'     => $lead->id,
            'user_id'     => auth()->id(),
            'type'        => 'status_change',
            'subject'     => 'Cambio stato',
            'body'        => sprintf(
                'Stato cambiato da "%s" a "%s" dalla kanban.',
                Lead::STATUSES[$oldStatus] ?? $oldStatus,
                Lead::STATUSES[$newStatus] ?? $newStatus,
            ),
            'from_status' => $oldStatus,
            'to_status'   => $newStatus,
            'occurred_at' => now(),
        ]);

        Notification::make()
            ->title('Stato aggiornato')
            ->success()
            ->send();
    }
}
