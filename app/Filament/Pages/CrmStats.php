<?php

namespace App\Filament\Pages;

use App\Models\Lead;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class CrmStats extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?string $navigationLabel = 'Statistiche';
    protected static ?string $title           = 'Statistiche CRM';
    protected static ?int    $navigationSort  = 3;

    protected static string $view = 'filament.pages.crm-stats';

    public static function canAccess(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['superadmin', 'Amministrazione', 'Segreteria']) ?? false;
    }

    public function getData(): array
    {
        // Lead per stato
        $perStato = Lead::select('status', DB::raw('count(*) as totale'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->status => $r->totale]);

        // Lead per fonte
        $perFonte = Lead::select('source', DB::raw('count(*) as totale'))
            ->groupBy('source')
            ->orderByDesc('totale')
            ->get();

        // Lead creati per mese (ultimi 6 mesi)
        $perMese = Lead::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mese'),
                DB::raw('count(*) as totale')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('mese')
            ->orderBy('mese')
            ->get();

        // Tempo medio conversione (da creazione a enrolled) in giorni
        $tempoMedio = Lead::where('status', 'enrolled')
            ->whereNotNull('converted_at')
            ->selectRaw('AVG(DATEDIFF(converted_at, created_at)) as giorni')
            ->value('giorni');

        // Tasso conversione totale
        $totale     = Lead::count();
        $iscritti   = Lead::where('status', 'enrolled')->count();
        $persi      = Lead::where('status', 'lost')->count();
        $tasso      = $totale > 0 ? round($iscritti / $totale * 100, 1) : 0;
        $tassoPerdita = $totale > 0 ? round($persi / $totale * 100, 1) : 0;

        // Top assegnatari per conversioni
        $topAssegnatari = Lead::where('status', 'enrolled')
            ->whereNotNull('assigned_to')
            ->select('assigned_to', DB::raw('count(*) as conversioni'))
            ->with('assignedTo:id,name')
            ->groupBy('assigned_to')
            ->orderByDesc('conversioni')
            ->limit(5)
            ->get();

        return compact(
            'perStato', 'perFonte', 'perMese',
            'tempoMedio', 'totale', 'iscritti', 'persi',
            'tasso', 'tassoPerdita', 'topAssegnatari'
        );
    }
}
