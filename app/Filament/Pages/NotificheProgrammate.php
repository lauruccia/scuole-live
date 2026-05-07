<?php

namespace App\Filament\Pages;

use App\Models\Contract;
use App\Models\Installment;
use App\Models\NotificationEmailLog;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

/**
 * Pagina di anteprima delle notifiche automatiche programmate.
 *
 * Mostra:
 *  - Rate in scadenza tra 5 giorni (tipo: installment_due_5_days)
 *  - Contratti che terminano tra 20 giorni (tipo: course_end_20_days)
 *
 * Per ciascuna voce indica se la notifica è già stata inviata (via NotificationEmailLog).
 */
class NotificheProgrammate extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Notifiche programmate';
    protected static ?string $title           = 'Notifiche programmate';
    protected static ?string $navigationGroup = 'Email';
    protected static ?string $slug            = 'notifiche-programmate';
    protected static string  $view            = 'filament.pages.notifiche-programmate';
    protected static ?int    $navigationSort  = 10;

    public array $rateInScadenza    = [];
    public array $corsiInScadenza   = [];
    public string $oggi             = '';

    public static function canAccess(): bool
    {
        $u = Filament::auth()->user();
        if (! $u) return false;

        return $u->hasAnyRole([
            'Superadmin', 'superadmin', 'super_admin',
            'Amministrazione', 'Segreteria',
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $today = Carbon::today();
        $this->oggi = $today->format('d/m/Y');

        // ── 1. Rate in scadenza tra 5 giorni ──────────────────────────────
        $targetInstallment = $today->copy()->addDays(5)->toDateString();

        $installments = Installment::query()
            ->with(['contract.students', 'contract.course'])
            ->whereDate('due_date', $targetInstallment)
            ->whereNull('paid_at')
            ->whereNull('deleted_at')
            ->get();

        $this->rateInScadenza = $installments->map(function (Installment $inst) {
            $contract   = $inst->contract;
            $studentNames = $contract?->students
                ->map(fn ($s) => trim($s->first_name . ' ' . $s->last_name))
                ->join(', ') ?? '—';

            $alreadySent = NotificationEmailLog::query()
                ->where('type', 'installment_due_5_days')
                ->where('contract_id', $contract?->id)
                ->where('installment_id', $inst->id)
                ->exists();

            return [
                'id'           => $inst->id,
                'studenti'     => $studentNames,
                'corso'        => $contract?->course?->name ?? '—',
                'data_scadenza'=> Carbon::parse($inst->due_date)->format('d/m/Y'),
                'importo'      => number_format((float) ($inst->amount ?? 0), 2, ',', '.') . ' €',
                'gia_inviata'  => $alreadySent,
            ];
        })->values()->toArray();

        // ── 2. Contratti in scadenza tra 20 giorni ────────────────────────
        $targetEnd = $today->copy()->addDays(20)->toDateString();

        $contracts = Contract::query()
            ->with(['students', 'course'])
            ->whereDate('ends_at', $targetEnd)
            ->whereNull('deleted_at')
            ->get();

        $this->corsiInScadenza = $contracts->map(function (Contract $contract) {
            $studentNames = $contract->students
                ->map(fn ($s) => trim($s->first_name . ' ' . $s->last_name))
                ->join(', ');

            $alreadySent = NotificationEmailLog::query()
                ->where('type', 'course_end_20_days')
                ->where('contract_id', $contract->id)
                ->exists();

            return [
                'id'          => $contract->id,
                'studenti'    => $studentNames ?: '—',
                'corso'       => $contract->course?->name ?? '—',
                'data_fine'   => $contract->ends_at
                    ? Carbon::parse($contract->ends_at)->format('d/m/Y')
                    : '—',
                'gia_inviata' => $alreadySent,
            ];
        })->values()->toArray();
    }
}
