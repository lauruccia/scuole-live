<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use App\Models\Course;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuovo corso'),

            Actions\Action::make('export_csv')
                ->label('Esporta CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    $records = $this->getFilteredTableQuery()
                        ->orderBy('name')
                        ->get();

                    $cols = ['#', 'Nome corso', 'Descrizione', 'Ore', 'Prezzo corso (euro)', 'Tassa iscrizione (euro)', 'Creato il'];

                    $callback = function () use ($records, $cols) {
                        $h = fopen('php://output', 'w');
                        fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
                        fputcsv($h, $cols, ';');
                        foreach ($records as $c) {
                            fputcsv($h, [
                                $c->id,
                                $c->name ?? '',
                                $c->description ?? '',
                                number_format((float) ($c->hours_purchased ?? 0), 0),
                                number_format((float) ($c->course_price ?? 0), 2, ',', '.'),
                                number_format((float) ($c->enrollment_fee ?? 0), 2, ',', '.'),
                                $c->created_at?->format('d/m/Y') ?? '',
                            ], ';');
                        }
                        fclose($h);
                    };

                    return response()->stream($callback, 200, [
                        'Content-Type'        => 'text/csv; charset=UTF-8',
                        'Content-Disposition' => 'attachment; filename="corsi_'.now()->format('Ymd_His').'.csv"',
                    ]);
                }),
        ];
    }
}
