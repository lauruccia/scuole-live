<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListLessons extends ListRecords
{
    protected static string $resource = LessonResource::class;

    public function getTabs(): array
    {
        return [
            'tutte' => Tab::make('Tutte le lezioni')
                ->icon('heroicon-o-list-bullet'),

            'recuperi' => Tab::make('Recuperi')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('recovery_of_lesson_id')),

            'da_recuperare' => Tab::make('Da recuperare')
                ->icon('heroicon-o-exclamation-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereNotNull('cancelled_at')
                    ->where('is_recoverable', true)
                    ->whereDoesntHave('recoveryLesson')),
        ];
    }

    protected function shouldPersistTableFiltersInSession(): bool
    {
        return false;
    }

    protected function getDefaultTableFilters(): ?array
    {
        return [
            'upcoming' => [
                'isActive' => true,
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_csv')
                ->label('Esporta CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    $records = $this->getFilteredTableQuery()
                        ->with([
                            'student:id,first_name,last_name',
                            'teacher:id,first_name,last_name,name,email',
                            'contract:id,course_id,academic_year',
                            'contract.course:id,name',
                        ])
                        ->orderBy('starts_at', 'asc')
                        ->get();

                    $cols = [
                        'Data/Ora inizio', 'Data/Ora fine', 'Durata (min)',
                        'Studente', 'Docente', 'Corso', 'Lingua', 'Anno didattico',
                        'Stato', 'Completata il', 'Annullata il', 'Motivo annullamento',
                        'Conta come consumata', 'Recuperabile', 'Lezione di recupero',
                        'Note', 'Compiti',
                    ];

                    $callback = function () use ($records, $cols) {
                        $h = fopen('php://output', 'w');
                        fprintf($h, chr(0xEF).chr(0xBB).chr(0xBF));
                        fputcsv($h, $cols, ';');

                        foreach ($records as $l) {
                            if ($l->recovery_of_lesson_id) {
                                $status = $l->counts_as_consumed ? 'Recupero completato' : 'Recupero da svolgere';
                            } elseif ($l->cancelled_at) {
                                $status = $l->is_recoverable ? 'Annullata (recuperabile)' : 'Annullata (consumata)';
                            } elseif ($l->completed_at) {
                                $status = 'Completata';
                            } else {
                                $status = 'Programmata';
                            }

                            $teacherName = '';
                            if ($t = $l->teacher) {
                                $teacherName = trim(($t->last_name ?? '') . ' ' . ($t->first_name ?? ''));
                                if ($teacherName === '') {
                                    $teacherName = $t->name ?? $t->email ?? '';
                                }
                            }

                            $studentName = '';
                            if ($s = $l->student) {
                                $studentName = trim(($s->last_name ?? '') . ' ' . ($s->first_name ?? ''));
                            }

                            $dur = (int) ($l->duration_minutes ?? 0);
                            if ($dur <= 0 && $l->starts_at && $l->ends_at) {
                                $dur = (int) Carbon::parse($l->starts_at)
                                    ->diffInMinutes(Carbon::parse($l->ends_at));
                            }

                            fputcsv($h, [
                                $l->starts_at?->format('d/m/Y H:i') ?? '',
                                $l->ends_at?->format('d/m/Y H:i') ?? '',
                                $dur ?: '',
                                $studentName,
                                $teacherName,
                                $l->contract?->course?->name ?? '',
                                $l->language_id ?? $l->contract?->language_id ?? '',
                                $l->contract?->academic_year ?? '',
                                $status,
                                $l->completed_at?->format('d/m/Y H:i') ?? '',
                                $l->cancelled_at?->format('d/m/Y H:i') ?? '',
                                $l->cancellation_reason ?? '',
                                $l->counts_as_consumed ? 'Sì' : 'No',
                                $l->is_recoverable ? 'Sì' : 'No',
                                $l->recovery_of_lesson_id ? 'Sì (#' . $l->recovery_of_lesson_id . ')' : 'No',
                                $l->notes ?? '',
                                $l->homework ?? '',
                            ], ';');
                        }

                        fclose($h);
                    };

                    return response()->stream($callback, 200, [
                        'Content-Type'        => 'text/csv; charset=UTF-8',
                        'Content-Disposition' => 'attachment; filename="lezioni_' . now()->format('Ymd_His') . '.csv"',
                    ]);
                }),
        ];
    }
}
