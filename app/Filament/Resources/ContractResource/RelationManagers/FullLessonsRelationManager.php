<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use App\Models\ContractStudent;
use App\Models\Lesson;
use App\Models\User;
use App\Services\FullLessonService;
use Illuminate\Support\Facades\Log;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FullLessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    protected static ?string $title = 'Lezioni FULL';

    protected static ?string $icon = 'heroicon-o-user-group';

    /**
     * Visibile solo per i contratti MIX (Lezioni personalizzate + FULL).
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ($ownerRecord->lesson_type ?? '') === 'Lezioni personalizzate + FULL';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    protected function getTableQuery(): Builder
    {
        return Lesson::query()
            ->where('contract_id', $this->getOwnerRecord()->id)
            ->where('is_full_lesson', true)
            ->withTrashed(false);
    }

    public function form(Form $form): Form
    {
        $contract = $this->getOwnerRecord();

        // Elenco beneficiari del contratto
        $beneficiaryOptions = ContractStudent::query()
            ->where('contract_id', $contract->id)
            ->get()
            ->mapWithKeys(fn ($cs) => [$cs->id => $cs->beneficiary_full_name ?: "Beneficiario #{$cs->id}"])
            ->toArray();

        // Elenco docenti
        $teacherOptions = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['docente', 'Docente']))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return $form->schema([
            Forms\Components\Section::make('Pianificazione lezione FULL')
                ->description('Inserisci data, ora e docente per pianificare la lezione. Lascia i campi vuoti se la lezione non è ancora stata fissata.')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('contract_student_id')
                            ->label('Beneficiario')
                            ->options($beneficiaryOptions)
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('teacher_id')
                            ->label('Docente')
                            ->options($teacherOptions)
                            ->nullable()
                            ->searchable()
                            ->placeholder('— non ancora assegnato —'),
                    ]),

                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Data e ora inizio')
                        ->nullable()
                        ->seconds(false)
                        ->helperText('Lascia vuoto se la lezione non è ancora stata fissata. La fine viene calcolata automaticamente (+60 min).'),

                    Forms\Components\TextInput::make('meet_url')
                        ->label('Link Meet')
                        ->url()
                        ->nullable()
                        ->maxLength(500),

                    Forms\Components\Textarea::make('notes')
                        ->label('Note')
                        ->nullable()
                        ->rows(2),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Lezioni FULL')
            ->description('Lezioni in immersione totale incluse nel contratto. La segreteria assegna data, ora e docente al momento della pianificazione.')
            ->defaultSort('lesson_number')
            ->columns([
                Tables\Columns\TextColumn::make('lesson_number')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('contractStudent.beneficiary_full_name')
                    ->label('Beneficiario')
                    ->sortable()
                    ->searchable(false),

                Tables\Columns\TextColumn::make('stato')
                    ->label('Stato')
                    ->badge()
                    ->state(function (Lesson $record): string {
                        if ($record->completed_at) {
                            return 'Completata';
                        }
                        if ($record->cancelled_at) {
                            return 'Annullata';
                        }
                        if ($record->starts_at) {
                            return 'Pianificata';
                        }
                        return 'Da definire';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Completata' => 'success',
                        'Annullata'  => 'danger',
                        'Pianificata' => 'info',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Data e ora')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('— non ancora pianificata —')
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Docente')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Durata')
                    ->formatStateUsing(fn ($state) => $state . ' min')
                    ->sortable(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stato_filter')
                    ->label('Stato')
                    ->options([
                        'da_definire' => 'Da definire',
                        'pianificata' => 'Pianificata',
                        'completata'  => 'Completata',
                        'annullata'   => 'Annullata',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if (! $value) {
                            return $query;
                        }
                        return match ($value) {
                            'da_definire' => $query->whereNull('starts_at')->whereNull('completed_at')->whereNull('cancelled_at'),
                            'pianificata' => $query->whereNotNull('starts_at')->whereNull('completed_at')->whereNull('cancelled_at'),
                            'completata'  => $query->whereNotNull('completed_at'),
                            'annullata'   => $query->whereNotNull('cancelled_at'),
                            default       => $query,
                        };
                    }),
            ])
            ->headerActions([
                // Azione per rigenerare i segnaposto FULL di uno specifico beneficiario
                Tables\Actions\Action::make('rigenera_full')
                    ->label('Aggiorna ore FULL beneficiario')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form(function (): array {
                        $contract = $this->getOwnerRecord();

                        $beneficiaryOptions = ContractStudent::query()
                            ->where('contract_id', $contract->id)
                            ->get()
                            ->mapWithKeys(fn ($cs) => [
                                $cs->id => ($cs->beneficiary_full_name ?: "Beneficiario #{$cs->id}")
                                    . ' — ore FULL assegnate: ' . ((int) ($cs->assigned_hours_full ?? 0)) . 'h',
                            ])
                            ->toArray();

                        return [
                            Forms\Components\Select::make('contract_student_id')
                                ->label('Beneficiario')
                                ->options($beneficiaryOptions)
                                ->required()
                                ->searchable(),

                            Forms\Components\TextInput::make('new_hours_full')
                                ->label('Nuove ore FULL')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->helperText('Le lezioni FULL non ancora pianificate verranno eliminate e ricreate. Le lezioni pianificate, completate e annullate non vengono modificate.'),
                        ];
                    })
                    ->action(function (array $data): void {
                        $contract  = $this->getOwnerRecord();
                        $cs        = ContractStudent::find((int) $data['contract_student_id']);
                        $newHours  = (float) $data['new_hours_full'];

                        if (! $cs) {
                            Notification::make()->title('Beneficiario non trovato')->danger()->send();
                            return;
                        }

                        $service = app(FullLessonService::class);
                        $error   = $service->validateFullHoursUpdate($contract, $cs, $newHours, (float) $contract->hours_full);

                        if ($error) {
                            Notification::make()
                                ->title('Modifica non consentita')
                                ->body($error)
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        try {
                            $service->regeneratePlaceholdersForBeneficiary($contract, $cs, $newHours);

                            Notification::make()
                                ->title('Lezioni FULL aggiornate')
                                ->body("Ore FULL aggiornate a {$newHours}h. Le lezioni non ancora pianificate sono state rigenerate.")
                                ->success()
                                ->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()
                                ->title('Errore nella rigenerazione')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Pianifica')
                    ->icon('heroicon-o-calendar')
                    ->modalHeading('Pianifica lezione FULL')
                    ->mutateRecordDataUsing(function (array $data): array {
                        return $data;
                    })
                    ->using(function (Lesson $record, array $data): Lesson {
                        // Imposta ends_at automaticamente se non fornito
                        if ($data['starts_at'] && ! ($data['ends_at'] ?? null)) {
                            $data['ends_at'] = \Carbon\Carbon::parse($data['starts_at'])
                                ->addMinutes($record->duration_minutes ?? 60)
                                ->toDateTimeString();
                        }

                        // Bug E: le lezioni FULL non hanno controllo sovrapposizioni per design
                        // (pianificate on-demand dalla segreteria con più studenti contemporaneamente).
                        // Tuttavia logghiamo e notifichiamo l'operatore se viene rilevata una
                        // sovrapposizione studente/docente, per permettere una verifica consapevole.
                        if (! empty($data['starts_at'])) {
                            $startsAt = \Carbon\Carbon::parse($data['starts_at']);
                            $endsAt   = \Carbon\Carbon::parse($data['ends_at']);

                            $studentOverlap = Lesson::query()
                                ->where('student_id', $record->student_id)
                                ->where('id', '!=', $record->id)
                                ->whereNull('cancelled_at')
                                ->whereNull('deleted_at')
                                ->where('starts_at', '<', $endsAt)
                                ->where('ends_at', '>', $startsAt)
                                ->exists();

                            if ($studentOverlap) {
                                Log::warning('FullLessonsRelationManager: sovrapposizione studente nella pianificazione FULL', [
                                    'lesson_id'  => $record->id,
                                    'student_id' => $record->student_id,
                                    'starts_at'  => $data['starts_at'],
                                    'ends_at'    => $data['ends_at'],
                                ]);
                                Notification::make()
                                    ->title('⚠️ Sovrapposizione studente')
                                    ->body('Lo studente ha già una lezione in questo orario. La pianificazione è stata salvata ma verificare il calendario.')
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            }

                            if (! empty($data['teacher_id'])) {
                                $teacherOverlap = Lesson::query()
                                    ->where('teacher_id', (int) $data['teacher_id'])
                                    ->where('id', '!=', $record->id)
                                    ->whereNull('cancelled_at')
                                    ->whereNull('deleted_at')
                                    ->where('starts_at', '<', $endsAt)
                                    ->where('ends_at', '>', $startsAt)
                                    ->exists();

                                if ($teacherOverlap) {
                                    Log::warning('FullLessonsRelationManager: sovrapposizione docente nella pianificazione FULL', [
                                        'lesson_id'  => $record->id,
                                        'teacher_id' => $data['teacher_id'],
                                        'starts_at'  => $data['starts_at'],
                                        'ends_at'    => $data['ends_at'],
                                    ]);
                                    Notification::make()
                                        ->title('⚠️ Sovrapposizione docente')
                                        ->body('Il docente ha già una lezione in questo orario. La pianificazione è stata salvata ma verificare il calendario.')
                                        ->warning()
                                        ->persistent()
                                        ->send();
                                }
                            }
                        }

                        $record->update($data);
                        return $record;
                    })
                    ->visible(fn (Lesson $record): bool => ! $record->completed_at && ! $record->cancelled_at),
            ])
            ->bulkActions([])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Nessuna lezione FULL')
            ->emptyStateDescription('Salva il contratto per generare automaticamente le lezioni FULL incluse nel contratto.');
    }
}
