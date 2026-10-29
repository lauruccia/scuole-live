<?php

namespace App\Filament\Resources\CourseResource\Pages;

use App\Filament\Resources\CourseResource;
use App\Models\Course;
use App\Models\SchoolSetting;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuovo corso'),

            Actions\Action::make('default_image')
                ->label('Foto predefinita')
                ->icon('heroicon-o-photo')
                ->color('gray')
                ->modalHeading('Foto predefinita dei corsi')
                ->modalDescription('Usata nella pagina di acquisto e nel catalogo per i corsi che non hanno una foto propria. Se la lasci vuota si usa l\'immagine standard del sito.')
                ->modalSubmitActionLabel('Salva')
                ->fillForm(fn (): array => ['course_default_image' => SchoolSetting::get('course_default_image') ?: null])
                ->form([
                    Forms\Components\FileUpload::make('course_default_image')
                        ->label('Foto')
                        ->helperText('Formato consigliato 16:9, massimo 4 MB.')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('courses/default')
                        ->maxSize(4096),
                ])
                ->action(function (array $data): void {
                    SchoolSetting::set('course_default_image', $data['course_default_image'] ?: null);

                    Notification::make()->title('Foto predefinita salvata')->success()->send();
                }),

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
