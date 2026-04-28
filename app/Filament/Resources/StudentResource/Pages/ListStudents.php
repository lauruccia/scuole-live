<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

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
                        ->orderBy('last_name')
                        ->orderBy('first_name')
                        ->get();

                    $cols = [
                        'Cognome','Nome','Email','Telefono','Codice Fiscale',
                        'Data di nascita','Luogo di nascita','Provincia nascita','Nazione nascita',
                        'Indirizzo','CAP','Citta','Provincia residenza','Nazione residenza',
                        'Minorenne','Nome genitore','Cognome genitore','Email genitore','Telefono genitore',
                        'Note','Creato il',
                    ];

                    $callback = function () use ($records, $cols) {
                        $h = fopen('php://output', 'w');
                        fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
                        fputcsv($h, $cols, ';');
                        foreach ($records as $s) {
                            fputcsv($h, [
                                $s->last_name ?? '',
                                $s->first_name ?? '',
                                $s->email ?? '',
                                $s->phone ?? '',
                                $s->fiscal_code ?? '',
                                $s->birth_date?->format('d/m/Y') ?? '',
                                $s->birth_place ?? '',
                                $s->birth_province ?? '',
                                $s->birth_country ?? '',
                                $s->residence_address ?? '',
                                $s->residence_zip ?? '',
                                $s->residence_city ?? '',
                                $s->residence_province ?? '',
                                $s->residence_country ?? '',
                                $s->is_minor ? 'Si' : 'No',
                                $s->parent_first_name ?? '',
                                $s->parent_last_name ?? '',
                                $s->parent_email ?? '',
                                $s->parent_phone ?? '',
                                $s->notes ?? '',
                                $s->created_at?->format('d/m/Y') ?? '',
                            ], ';');
                        }
                        fclose($h);
                    };

                    return response()->stream($callback, 200, [
                        'Content-Type'        => 'text/csv; charset=UTF-8',
                        'Content-Disposition' => 'attachment; filename="studenti_'.now()->format('Ymd_His').'.csv"',
                    ]);
                }),
        ];
    }
}
