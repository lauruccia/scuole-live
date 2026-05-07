<?php

namespace App\Filament\Studente\Resources;

use App\Filament\Studente\Concerns\HasStudentScope;
use App\Filament\Studente\Resources\StudentLessonResource\Pages;
use App\Models\Lesson;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentLessonResource extends Resource
{
    use HasStudentScope;

    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Lezioni';
    protected static ?string $navigationGroup = 'Area Studente';
    protected static ?int $navigationSort = 20;
    protected static ?string $slug = 'lezioni';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    protected static function getStudentIds(): array
    {
        if (! auth()->check()) {
            return [];
        }

        return auth()->user()
            ->students()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function getEloquentQuery(): Builder
    {
        $studentIds = static::getStudentIds();

        $query = parent::getEloquentQuery()
            ->with(['contract.course', 'teacher', 'student']);

        if (empty($studentIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($studentIds) {
            $q->whereIn('student_id', $studentIds)
              ->orWhereHas('contract.students', function (Builder $subQuery) use ($studentIds) {
                  $subQuery->whereIn('students.id', $studentIds);
              });
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Inizio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Fine')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Studente')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('contract.course.name')
                    ->label('Corso')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher_name')
                    ->label('Docente')
                    ->getStateUsing(function (Lesson $record): string {
                        $full = trim(($record->teacher?->first_name ?? '') . ' ' . ($record->teacher?->last_name ?? ''));
                        return $full !== '' ? $full : ($record->teacher?->name ?? '—');
                    }),

                Tables\Columns\TextColumn::make('status_label')
                    ->label('Stato')
                    ->badge()
                    ->getStateUsing(function (Lesson $record): string {
                        if ($record->cancelled_at && $record->is_recoverable) {
                            return 'Da recuperare';
                        }

                        if ($record->cancelled_at) {
                            return 'Annullata';
                        }

                        // Allineato con la logica admin: usa counts_as_consumed
                        // (che viene settato sia da completed_at che da annullamento <24h)
                        if ($record->counts_as_consumed) {
                            return 'Completata';
                        }

                        return 'Programmata';
                    })
                    ->color(function (string $state): string {
                        return match ($state) {
                            'Completata'    => 'success',
                            'Annullata'     => 'danger',
                            'Da recuperare' => 'warning',
                            default         => 'info',
                        };
                    }),

                Tables\Columns\IconColumn::make('meet_url')
                    ->label('Meet')
                    ->boolean()
                    ->getStateUsing(fn (Lesson $record): bool => filled($record->meet_url)),
            ])
            ->actions([
                Tables\Actions\Action::make('vedi_compiti')
                    ->label('Vedi note e compiti')
                    ->icon('heroicon-o-book-open')
                    ->color('primary')
                    ->modalHeading('Note e Compiti')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Chiudi')
                    ->modalContent(fn ($record) => view('filament.studente.modals.lesson-homework', [
                        'lesson' => $record,
                    ])),
            ])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccessStudentPanel();
    }

    public static function canView($record): bool
    {
        $studentIds = static::getStudentIds();

        if (empty($studentIds)) {
            return false;
        }

        if (in_array((int) $record->student_id, $studentIds, true)) {
            return true;
        }

        return $record->contract()
            ->whereHas('students', function (Builder $query) use ($studentIds) {
                $query->whereIn('students.id', $studentIds);
            })
            ->exists();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentLessons::route('/'),
        ];
    }
}