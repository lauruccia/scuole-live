<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['superadmin', 'Amministrazione', 'Segreteria']) ?? false;
    }

    protected function getStats(): array
    {
        $nuovi       = Lead::where('status', 'new')->count();
        $contattati  = Lead::where('status', 'contacted')->count();
        $proposte    = Lead::where('status', 'proposal_sent')->count();
        $iscritti    = Lead::where('status', 'enrolled')->count();
        $persi       = Lead::where('status', 'lost')->count();

        $followupOggi    = Lead::followupToday()->count();
        $followupScaduti = Lead::followupOverdue()->count();

        // Tasso conversione (ultimi 90 giorni)
        $totale90    = Lead::where('created_at', '>=', now()->subDays(90))->count();
        $convertiti90 = Lead::where('status', 'enrolled')
            ->where('created_at', '>=', now()->subDays(90))
            ->count();
        $tasso = $totale90 > 0 ? round($convertiti90 / $totale90 * 100) : 0;

        return [
            Stat::make('Nuovi lead', $nuovi)
                ->description('Da contattare')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('gray')
                ->url(route('filament.admin.resources.leads.index', ['tableFilters[status][value]' => 'new'])),

            Stat::make('Follow-up oggi', $followupOggi)
                ->description($followupScaduti > 0 ? "{$followupScaduti} scaduti ⚠️" : 'Nessuno scaduto')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($followupScaduti > 0 ? 'danger' : ($followupOggi > 0 ? 'warning' : 'success'))
                ->url(route('filament.admin.resources.leads.index', ['tableFilters[followup_today][isActive]' => true])),

            Stat::make('In trattativa', $contattati + $proposte)
                ->description("{$contattati} contattati · {$proposte} proposte inviate")
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('info')
                ->url(route('filament.admin.pages.lead-kanban')),

            Stat::make('Conversione 90gg', "{$tasso}%")
                ->description("{$convertiti90} iscritti su {$totale90} lead")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($tasso >= 30 ? 'success' : ($tasso >= 15 ? 'warning' : 'danger')),
        ];
    }
}
