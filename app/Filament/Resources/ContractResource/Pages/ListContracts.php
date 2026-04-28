<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListContracts extends ListRecords
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('export_csv')
                ->label('Esporta CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    $records = $this->getFilteredTableQuery()
                        ->with([
                            'course:id,name',
                            'students:id,first_name,last_name',
                            'installments',
                        ])
                        ->orderBy('id', 'asc')
                        ->get();

                    $cols = [
                        '#', 'Studenti', 'Tipo', 'Intestatario', 'Corso',
                        'Anno didattico', 'Stato', 'Tipo lezione', 'Lingua',
                        'Inizio', 'Fine', 'Iscrizione',
                        'Ore acquistate', 'Ore fruite', 'Ore rimanenti',
                        'Prezzo corso (€)', 'Tassa iscrizione (€)', 'Totale (€)',
                        'Acconto (€)', 'Residuo (€)',
                        'Modalità pagamento', 'N° rate', 'Rate pagate', 'Rate da pagare',
                        'Note', 'Creato il',
                    ];

                    $callback = function () use ($records, $cols) {
                        $h = fopen('php://output', 'w');
                        fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
                        fputcsv($h, $cols, ';');

                        foreach ($records as $c) {
                            $students = $c->students->map(fn ($s) =>
                                trim(($s->last_name ?? '') . ' ' . ($s->first_name ?? ''))
                            )->implode(' / ');

                            $statusLabel = match ($c->status ?? 'active') {
                                'active'    => 'Attivo',
                                'completed' => 'Completato',
                                'suspended' => 'Sospeso',
                                'cancelled' => 'Annullato',
                                default     => $c->status ?? '',
                            };

                            $paymentLabel = match ($c->payment_mode ?? '') {
                                'once'         => 'Unica soluzione',
                                'installments' => 'Rate',
                                default        => $c->payment_mode ?? '',
                            };

                            $installments     = $c->installments;
                            $totalInstall     = $installments->count();
                            $paidInstall      = $installments->filter(
                                fn ($i) => $i->status === 'paid' || ! is_null($i->paid_at)
                            )->count();
                            $unpaidInstall    = $totalInstall - $paidInstall;

                            $hoursRem = max(0, (float) ($c->hours_purchased ?? 0) - (float) ($c->hours_consumed ?? 0));

                            fputcsv($h, [
                                $c->id,
                                $students,
                                $c->billing_type === 'company' ? 'Azienda' : 'Privato',
                                $c->billing_display_name ?? '',
                                $c->course?->name ?? '',
                                $c->academic_year ?? '',
                                $statusLabel,
                                $c->lesson_type ?? '',
                                $c->language_id ?? '',
                                $c->starts_at?->format('d/m/Y') ?? '',
                                $c->ends_at?->format('d/m/Y') ?? '',
                                $c->admission_date?->format('d/m/Y') ?? '',
                                number_format((float) ($c->hours_purchased ?? 0), 2, ',', ''),
                                number_format((float) ($c->hours_consumed ?? 0), 2, ',', ''),
                                number_format($hoursRem, 2, ',', ''),
                                number_format((float) ($c->course_price ?? 0), 2, ',', '.'),
                                number_format((float) ($c->enrollment_fee ?? 0), 2, ',', '.'),
                                number_format((float) ($c->total ?? 0), 2, ',', '.'),
                                number_format((float) ($c->deposit ?? 0), 2, ',', '.'),
                                number_format((float) ($c->residual ?? 0), 2, ',', '.'),
                                $paymentLabel,
                                $totalInstall ?: '',
                                $paidInstall ?: '',
                                $unpaidInstall ?: '',
                                $c->notes ?? '',
                                $c->created_at?->format('d/m/Y') ?? '',
                            ], ';');
                        }

                        fclose($h);
                    };

                    return response()->stream($callback, 200, [
                        'Content-Type'        => 'text/csv; charset=UTF-8',
                        'Content-Disposition' => 'attachment; filename="contratti_' . now()->format('Ymd_His') . '.csv"',
                    ]);
                }),
        ];
    }
}
