<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use App\Models\Lesson;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ContractStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.contract-status';
    protected static ?int   $sort = 2;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['superadmin', 'Amministrazione', 'Segreteria']) ?? false;
    }

    public function getStats(): array
    {
        // 1. Contratti attivi con ore quasi esaurite (≤ 20% residue, ma ancora > 0)
        $lowHoursContracts = Contract::query()
            ->whereIn('status', ['active', null, ''])
            ->whereRaw('hours_purchased > 0')
            ->whereRaw('hours_consumed >= (hours_purchased * 0.80)')
            ->whereRaw('hours_consumed < hours_purchased')
            ->orderByRaw('(hours_purchased - hours_consumed) ASC')
            ->limit(10)
            ->get(['id', 'billing_first_name', 'billing_last_name', 'company_name',
                   'hours_purchased', 'hours_consumed', 'academic_year']);

        // 2. Contratti con ore esaurite (consumed >= purchased) ma ancora attivi
        $exhaustedContracts = Contract::query()
            ->whereIn('status', ['active', null, ''])
            ->whereRaw('hours_purchased > 0')
            ->whereRaw('hours_consumed >= hours_purchased')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'billing_first_name', 'billing_last_name', 'company_name',
                   'hours_purchased', 'hours_consumed', 'academic_year']);

        // 3. Lezioni future in eccesso:
        //    contratti dove il totale ore delle lezioni future non svolte
        //    supera le ore residue del contratto
        $excessLessonsRaw = DB::table('lessons')
            ->join('contracts', 'lessons.contract_id', '=', 'contracts.id')
            ->where('lessons.starts_at', '>=', Carbon::today())
            ->whereNull('lessons.cancelled_at')
            ->where('lessons.counts_as_consumed', 0)
            ->whereRaw('contracts.hours_purchased > 0')
            ->groupBy(
                'lessons.contract_id',
                'contracts.billing_first_name',
                'contracts.billing_last_name',
                'contracts.company_name',
                'contracts.hours_purchased',
                'contracts.hours_consumed',
                'contracts.academic_year'
            )
            ->havingRaw(
                'SUM(COALESCE(lessons.duration_minutes, 60) / 60.0) > (contracts.hours_purchased - contracts.hours_consumed)'
            )
            ->select(
                'lessons.contract_id as id',
                'contracts.billing_first_name',
                'contracts.billing_last_name',
                'contracts.company_name',
                'contracts.hours_purchased',
                'contracts.hours_consumed',
                'contracts.academic_year',
                DB::raw('SUM(COALESCE(lessons.duration_minutes, 60) / 60.0) as future_hours')
            )
            ->orderByDesc('future_hours')
            ->limit(10)
            ->get();

        // 4. Contratti attivi in scadenza nei prossimi 30 giorni
        $expiringContracts = Contract::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [Carbon::today(), Carbon::today()->addDays(30)])
            ->orderBy('ends_at')
            ->limit(10)
            ->get(['id', 'billing_first_name', 'billing_last_name', 'company_name',
                   'ends_at', 'hours_purchased', 'hours_consumed', 'academic_year']);

        return [
            'low_hours'       => $lowHoursContracts,
            'exhausted'       => $exhaustedContracts,
            'excess_lessons'  => $excessLessonsRaw,
            'expiring'        => $expiringContracts,
        ];
    }

    protected static function contractName($row): string
    {
        $first = trim((string) ($row->billing_first_name ?? ''));
        $last  = trim((string) ($row->billing_last_name ?? ''));
        $full  = trim($last . ' ' . $first);
        if ($full !== '') return $full;
        return (string) ($row->company_name ?? "Contratto #{$row->id}");
    }

    public function getContractName($row): string
    {
        return static::contractName($row);
    }
}
