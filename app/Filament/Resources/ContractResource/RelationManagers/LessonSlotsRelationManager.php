<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use App\Models\ContractStudent;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LessonSlotsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessonSlots';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')
                ->label('Studente')
                ->placeholder('Seleziona studente del contratto')
                ->searchable()
                ->preload()
                ->options(function (): array {
                    $contract = $this->getOwnerRecord();
                    if (! $contract) return [];

                    $studentIds = ContractStudent::query()
                        ->where('contract_id', $contract->id)
                        ->whereNotNull('student_id')
                        ->pluck('student_id')
                        ->unique()
                        ->values();

                    if ($studentIds->isEmpty()) return [];

                    return Student::query()
                        ->whereIn('id', $studentIds)
                        ->orderBy('last_name')
                        ->orderBy('first_name')
                        ->get()
                        ->mapWithKeys(fn (Student $s) => [
                            $s->id => $s->full_name
                                ?? trim(($s->last_name ?? '') . ' ' . ($s->first_name ?? ''))
                                ?: ('Studente #' . $s->id),
                        ])
                        ->toArray();
                })
                ->default(function () {
                    $contract = $this->getOwnerRecord();
                    if (! $contract) return null;

                    $ids = ContractStudent::query()
                        ->where('contract_id', $contract->id)
                        ->whereNotNull('student_id')
                        ->pluck('student_id')
                        ->unique()
                        ->values();

                    return $ids->count() === 1 ? (int) $ids->first() : null;
                })
                ->required(),

            Forms\Components\Select::make('teacher_id')
                ->label('Docente')
                ->searchable()
                ->preload()
                ->options(fn () => User::query()
                    ->role('Docente')
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
                        return [$u->id => $label];
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

            Forms\Components\TextInput::make('duration_minutes')
                ->label('Durata (minuti)')
                ->numeric()
                ->minValue(15)
                ->step(5)
                ->default(60)
                ->required(),

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
                        ?: '—'
                    )
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

                Tables\Columns\TextColumn::make('weekly_time')->label('Orario')->sortable(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Docente')
                    ->getStateUsing(fn ($record) => trim((string) ($record->teacher?->name ?? '')) ?: ($record->teacher?->email ?? '—'))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')->label('Attivo')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
