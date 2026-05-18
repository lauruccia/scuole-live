<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use App\Models\ContractLessonSlot;
use App\Models\ContractStudent;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class LessonSlotsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessonSlots';

    protected function getContractStudentIds(?int $includeStudentId = null): array
    {
        $contract = $this->getOwnerRecord();

        if (! $contract) {
            return [];
        }

        $ids = ContractStudent::query()
            ->where('contract_id', $contract->id)
            ->whereNotNull('student_id')
            ->pluck('student_id')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        if ($includeStudentId) {
            $ids[] = (int) $includeStudentId;
            $ids = array_values(array_unique($ids));
        }

        return $ids;
    }

    protected function getStudentOptions(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return Student::query()
            ->whereIn('id', $ids)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(function (Student $s) {
                $label = $s->full_name
                    ?? trim(($s->last_name ?? '') . ' ' . ($s->first_name ?? ''));

                if ($label === '') {
                    $label = 'Studente #' . $s->id;
                }

                return [(int) $s->id => $label];
            })
            ->toArray();
    }

    protected function normalizeDurationMinutes(mixed $value): int
    {
        $duration = (int) $value;

        if (! in_array($duration, [30, 60, 90, 120], true)) {
            $duration = 60;
        }

        return $duration;
    }

    /**
     * Bug G: verifica che non esista già uno slot identico per lo stesso
     * contratto/studente/giorno/orario. Se trovato, mostra una notifica
     * di errore e blocca il salvataggio con una ValidationException.
     *
     * @param  array      $data     Dati del form normalizzati
     * @param  int|null   $excludeId  ID dello slot da escludere (edit)
     * @throws ValidationException
     */
    protected function guardDuplicateSlot(array $data, ?int $excludeId = null): void
    {
        $contract = $this->getOwnerRecord();

        $query = ContractLessonSlot::query()
            ->where('contract_id', $contract->id)
            ->where('student_id', (int) ($data['student_id'] ?? 0))
            ->where('weekly_day', (int) ($data['weekly_day'] ?? 0))
            ->where('weekly_time', (string) ($data['weekly_time'] ?? ''));

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            Notification::make()
                ->title('Slot duplicato')
                ->body('Esiste già uno slot per questo studente con lo stesso giorno e orario. Modifica l\'orario o elimina lo slot esistente.')
                ->danger()
                ->persistent()
                ->send();

            throw ValidationException::withMessages([
                'weekly_time' => 'Esiste già uno slot per questo studente con lo stesso giorno e orario.',
            ]);
        }
    }

    protected function formatDurationLabel(int $minutes): string
    {
        return match ($minutes) {
            30  => '30 min',
            60  => '1 ora',
            90  => '1 ora e 30 min',
            120 => '2 ore',
            default => $minutes . ' min',
        };
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Hidden::make('starts_at')
                ->default(function (?Model $record) {
                    if ($record?->starts_at) {
                        return $record->starts_at;
                    }

                    $contract = $this->getOwnerRecord();

                    return $contract?->starts_at ? (string) $contract->starts_at : null;
                })
                ->dehydrated(true)
                ->nullable(),

            Placeholder::make('student_auto_info')
                ->label('Studente')
                ->content(function (?Model $record) {
                    $currentStudentId = $record?->student_id ? (int) $record->student_id : null;
                    $ids = $this->getContractStudentIds($currentStudentId);

                    if (count($ids) === 1) {
                        $s = Student::find((int) $ids[0]);

                        return $s?->full_name
                            ?? trim(($s?->last_name ?? '') . ' ' . ($s?->first_name ?? ''))
                            ?? ('Studente #' . $ids[0]);
                    }

                    return '—';
                })
                ->visible(function (?Model $record) {
                    $currentStudentId = $record?->student_id ? (int) $record->student_id : null;
                    $ids = $this->getContractStudentIds($currentStudentId);

                    return count($ids) === 1;
                })
                ->dehydrated(false),

            Hidden::make('student_id')
                ->default(function (?Model $record) {
                    if ($record?->student_id) {
                        return (int) $record->student_id;
                    }

                    $ids = $this->getContractStudentIds();

                    return count($ids) === 1 ? (int) $ids[0] : null;
                })
                ->required()
                ->visible(function (?Model $record) {
                    $currentStudentId = $record?->student_id ? (int) $record->student_id : null;
                    $ids = $this->getContractStudentIds($currentStudentId);

                    return count($ids) === 1;
                }),

            Forms\Components\Select::make('student_id')
                ->label('Studente')
                ->placeholder('Seleziona studente del contratto')
                ->searchable()
                ->preload()
                ->options(function (?Model $record): array {
                    $currentStudentId = $record?->student_id ? (int) $record->student_id : null;
                    $ids = $this->getContractStudentIds($currentStudentId);

                    return $this->getStudentOptions($ids);
                })
                ->getSearchResultsUsing(function (string $search, ?Model $record): array {
                    $currentStudentId = $record?->student_id ? (int) $record->student_id : null;
                    $ids = $this->getContractStudentIds($currentStudentId);

                    if (empty($ids)) {
                        return [];
                    }

                    $s = trim($search);
                    if ($s === '') {
                        return $this->getStudentOptions($ids);
                    }

                    return Student::query()
                        ->whereIn('id', $ids)
                        ->where(function ($q) use ($s) {
                            $q->where('first_name', 'like', "%{$s}%")
                                ->orWhere('last_name', 'like', "%{$s}%")
                                ->orWhereRaw("concat(coalesce(first_name,''),' ',coalesce(last_name,'')) like ?", ["%{$s}%"])
                                ->orWhereRaw("concat(coalesce(last_name,''),' ',coalesce(first_name,'')) like ?", ["%{$s}%"]);
                        })
                        ->orderBy('last_name')
                        ->orderBy('first_name')
                        ->limit(25)
                        ->get()
                        ->mapWithKeys(function (Student $st) {
                            $label = $st->full_name
                                ?? trim(($st->last_name ?? '') . ' ' . ($st->first_name ?? ''));

                            if ($label === '') {
                                $label = 'Studente #' . $st->id;
                            }

                            return [(int) $st->id => $label];
                        })
                        ->toArray();
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    if (! $value) {
                        return null;
                    }

                    $s = Student::find((int) $value);

                    return $s?->full_name
                        ?? trim(($s?->last_name ?? '') . ' ' . ($s?->first_name ?? ''))
                        ?: ('Studente #' . (int) $value);
                })
                ->required()
                ->visible(function (?Model $record) {
                    $currentStudentId = $record?->student_id ? (int) $record->student_id : null;
                    $ids = $this->getContractStudentIds($currentStudentId);

                    return count($ids) !== 1;
                }),

            Forms\Components\Select::make('teacher_id')
                ->label('Docente')
                ->searchable()
                ->preload()
                ->options(fn () => User::query()
                    ->whereHas('roles', fn ($q) => $q->whereIn('name', ['docente', 'Docente']))
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(function (User $u) {
                        $label = trim((string) ($u->name ?? ''));
                        if ($label === '') {
                            $label = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
                        }
                        if ($label === '') {
                            $label = $u->email ?: ('Docente #' . $u->id);
                        }

                        return [(int) $u->id => $label];
                    })
                    ->toArray()
                )
                ->nullable(),

            Forms\Components\Select::make('weekly_day')
                ->label('Giorno')
                ->options([
                    1 => 'Lunedì',
                    2 => 'Martedì',
                    3 => 'Mercoledì',
                    4 => 'Giovedì',
                    5 => 'Venerdì',
                    6 => 'Sabato',
                    7 => 'Domenica',
                ])
                ->required(),

            Forms\Components\TimePicker::make('weekly_time')
                ->label('Orario')
                ->seconds(false)
                ->required(),

            Forms\Components\Select::make('duration_minutes')
                ->label('Durata lezione')
                ->options([
                    30  => '30 min',
                    60  => '1 ora',
                    90  => '1 ora e 30 min',
                    120 => '2 ore',
                ])
                ->default(60)
                ->required()
                ->afterStateHydrated(function ($state, callable $set) {
                    $set('duration_minutes', $this->normalizeDurationMinutes($state));
                })
                ->dehydrateStateUsing(fn ($state) => $this->normalizeDurationMinutes($state)),

            Forms\Components\Toggle::make('is_active')
                ->label('Attivo')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('weekly_time')
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Studente')
                    ->getStateUsing(fn ($record) => $record->student?->full_name
                        ?? trim(($record->student?->last_name ?? '') . ' ' . ($record->student?->first_name ?? ''))
                        ?: '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('weekly_day')
                    ->label('Giorno')
                    ->formatStateUsing(fn ($state) => match ((int) $state) {
                        1 => 'Lunedì',
                        2 => 'Martedì',
                        3 => 'Mercoledì',
                        4 => 'Giovedì',
                        5 => 'Venerdì',
                        6 => 'Sabato',
                        7 => 'Domenica',
                        default => '—',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('weekly_time')
                    ->label('Orario')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Durata')
                    ->formatStateUsing(fn ($state) => $this->formatDurationLabel((int) $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Docente')
                    ->getStateUsing(fn ($record) => trim((string) ($record->teacher?->name ?? '')) ?: ($record->teacher?->email ?? '—'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Attivo')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $contract = $this->getOwnerRecord();

                        if (empty($data['starts_at']) && $contract?->starts_at) {
                            $data['starts_at'] = (string) $contract->starts_at;
                        }

                        $data['duration_minutes'] = $this->normalizeDurationMinutes($data['duration_minutes'] ?? null);

                        // Bug G: blocca slot duplicati (stesso contratto/studente/giorno/ora)
                        $this->guardDuplicateSlot($data);

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, Model $record): array {
                        $data['duration_minutes'] = $this->normalizeDurationMinutes($data['duration_minutes'] ?? null);

                        // Bug G: blocca slot duplicati escludendo lo slot corrente
                        $this->guardDuplicateSlot($data, (int) $record->id);

                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}