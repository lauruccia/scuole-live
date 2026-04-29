<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\TeacherHomeworkResource\Pages;
use App\Models\Contract;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Student;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TeacherHomeworkResource extends Resource
{
    protected static ?string $model = Homework::class;

    protected static ?string $navigationIcon  = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationGroup = 'Didattica';
    protected static ?string $navigationLabel = 'Esercitazioni con restituzione';
    protected static ?string $modelLabel      = 'Esercitazione';
    protected static ?string $pluralModelLabel = 'Esercitazioni con restituzione';
    protected static ?int    $navigationSort  = 4;

    // Solo i compiti assegnati da questo docente
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('teacher_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        $teacherId = Auth::id();

        return $form->schema([

            // ── Step 1: Studente ──────────────────────────────────────────────
            Section::make('Studente')->schema([
                Select::make('student_search')
                    ->label('Cerca studente')
                    ->placeholder('Digita cognome o nome…')
                    ->options(function () use ($teacherId): array {
                        // Studenti con cui il docente ha svolto almeno una lezione
                        return Student::query()
                            ->whereHas('lessons', fn ($q) => $q->where('lessons.teacher_id', $teacherId))
                            ->orderBy('last_name')
                            ->orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn (Student $s) => [
                                $s->id => trim($s->last_name . ' ' . $s->first_name)
                                    . ($s->email ? ' ‹' . $s->email . '›' : ''),
                            ])->toArray();
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) use ($teacherId) {
                        $state = $state ? (int) $state : null;
                        if (! $state) {
                            $set('contract_id', null);
                            $set('language', null);
                            return;
                        }

                        // Contratto dello studente su cui il docente ha svolto lezioni
                        $contract = Contract::whereHas('students', fn ($q) => $q->where('students.id', $state))
                            ->whereHas('lessons', fn ($q) => $q->where('lessons.teacher_id', $teacherId))
                            ->where('status', 'active')
                            ->latest('id')
                            ->first()
                            ?? Contract::whereHas('students', fn ($q) => $q->where('students.id', $state))
                                ->whereHas('lessons', fn ($q) => $q->where('lessons.teacher_id', $teacherId))
                                ->latest('id')
                                ->first();

                        if ($contract) {
                            $set('contract_id', $contract->id);

                            $langs = is_array($contract->languages) ? $contract->languages : [];
                            if (count($langs) === 1) {
                                $set('language', $langs[0]);
                            }
                        }
                    }),

                Select::make('contract_id')
                    ->label('Contratto')
                    ->required()
                    ->searchable()
                    ->live()
                    ->options(function (Get $get) use ($teacherId): array {
                        $studentId = $get('student_search') ? (int) $get('student_search') : null;
                        $currentId = $get('contract_id')    ? (int) $get('contract_id')    : null;

                        $query = Contract::query()
                            ->whereHas('lessons', fn ($q) => $q->where('lessons.teacher_id', $teacherId))
                            ->orderByDesc('id');

                        if ($studentId) {
                            $query->whereHas('students', fn ($q) => $q->where('students.id', $studentId));
                        } elseif ($currentId) {
                            $query->where('id', $currentId);
                        } else {
                            $query->limit(30);
                        }

                        return $query->get()
                            ->mapWithKeys(fn ($c) => [$c->id => self::contractLabel($c)])
                            ->toArray();
                    })
                    ->getSearchResultsUsing(function (string $search) use ($teacherId): array {
                        return Contract::query()
                            ->whereHas('lessons', fn ($q) => $q->where('lessons.teacher_id', $teacherId))
                            ->where(fn ($q) => $q
                                ->where('billing_last_name', 'like', "%{$search}%")
                                ->orWhere('billing_first_name', 'like', "%{$search}%")
                                ->orWhere('company_name',      'like', "%{$search}%")
                            )
                            ->limit(20)
                            ->get()
                            ->mapWithKeys(fn ($c) => [
                                $c->id => self::contractLabel($c),
                            ])->toArray();
                    })
                    ->getOptionLabelUsing(fn ($v): ?string =>
                        $v ? self::contractLabel(Contract::find($v)) : null
                    )
                    ->helperText('Precompilato automaticamente. Puoi cambiarlo manualmente.'),
            ]),

            // ── Step 2: Dettagli ──────────────────────────────────────────────
            Section::make('Dettagli esercitazione')->schema([
                Grid::make(2)->schema([
                    TextInput::make('title')
                        ->label('Titolo')
                        ->required()
                        ->maxLength(190)
                        ->placeholder('Es. Esercizi pagina 42-44'),

                    Select::make('language')
                        ->label('Lingua')
                        ->options([
                            'Arabo'                  => 'Arabo',
                            'Francese'               => 'Francese',
                            'Inglese'                => 'Inglese',
                            'Spagnolo'               => 'Spagnolo',
                            'Tedesco'                => 'Tedesco',
                            'Italiano per stranieri' => 'Italiano per stranieri',
                        ])
                        ->nullable()
                        ->searchable(),
                ]),

                DateTimePicker::make('due_at')
                    ->label('Scadenza consegna')
                    ->nullable()
                    ->native(false)
                    ->displayFormat('d/m/Y H:i'),

                Textarea::make('instructions')
                    ->label('Istruzioni')
                    ->rows(4)
                    ->nullable()
                    ->placeholder('Descrivi cosa deve fare lo studente…'),
            ]),

            // ── Allegato opzionale ────────────────────────────────────────────
            Section::make('Allegato traccia')->schema([
                FileUpload::make('attachment_path')
                    ->label('File traccia')
                    ->disk('public')
                    ->directory('homeworks/assignments')
                    ->preserveFilenames()
                    ->maxSize(20480)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'image/jpeg', 'image/png',
                    ])
                    ->nullable(),
                \Filament\Forms\Components\Hidden::make('attachment_name'),
            ])->collapsible()->collapsed(),
        ]);
    }

    private static function contractLabel(?Contract $c): string
    {
        if (! $c) return '—';
        $name = trim(($c->billing_last_name ?? '') . ' ' . ($c->billing_first_name ?? '')) ?: ($c->company_name ?? '—');
        $langs = is_array($c->languages) && count($c->languages)
            ? ' · ' . implode(', ', $c->languages)
            : '';
        return '#' . $c->id . ' — ' . $name . $langs;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Compito')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Homework $r): string => $r->language ?? ''),

                Tables\Columns\TextColumn::make('student_label')
                    ->label('Studente')
                    ->getStateUsing(function (Homework $r): string {
                        $student = $r->contract?->students()->first();
                        if ($student) return trim($student->last_name . ' ' . $student->first_name);
                        $c = $r->contract;
                        if (! $c) return '—';
                        return trim(($c->billing_last_name ?? '') . ' ' . ($c->billing_first_name ?? '')) ?: '—';
                    }),

                Tables\Columns\TextColumn::make('due_at')
                    ->label('Scadenza')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn (Homework $r): string => $r->isPastDue() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('submissions_summary')
                    ->label('Consegne')
                    ->getStateUsing(fn (Homework $r): string =>
                        $r->submissions()->where('status', 'submitted')->count() . ' da valutare'
                    )
                    ->badge()
                    ->color('warning'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('consegne')
                    ->label('Consegne')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->color('info')
                    ->modalHeading(fn (Homework $r): string => 'Consegne — ' . $r->title)
                    ->modalContent(fn (Homework $r) => view(
                        'filament.admin.homework-submissions-modal',
                        ['homework' => $r]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Chiudi'),

                Tables\Actions\Action::make('valuta')
                    ->label('Valuta')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (Homework $r): bool => $r->submissions()->where('status', 'submitted')->exists())
                    ->modalHeading(fn (Homework $r): string => 'Valuta consegna — ' . $r->title)
                    ->form(function (Homework $r): array {
                        $sub = $r->submissions()->where('status', 'submitted')->with('student')->first();
                        $fileLink = $sub?->file_path
                            ? '<a href="' . Storage::disk('public')->url($sub->file_path) . '" target="_blank" class="text-primary-600 hover:underline">📎 ' . ($sub->file_name ?? 'scarica') . '</a>'
                            : '—';
                        return [
                            \Filament\Forms\Components\Placeholder::make('studente')
                                ->label('Studente')
                                ->content($sub?->student?->full_name ?? '—'),
                            \Filament\Forms\Components\Placeholder::make('consegnato_il')
                                ->label('Consegnato il')
                                ->content($sub?->submitted_at?->format('d/m/Y H:i') ?? '—'),
                            \Filament\Forms\Components\Placeholder::make('nota_studente')
                                ->label('Nota studente')
                                ->content($sub?->student_note ?? '—'),
                            \Filament\Forms\Components\Placeholder::make('file_allegato')
                                ->label('File allegato')
                                ->content(new \Illuminate\Support\HtmlString($fileLink)),
                            \Filament\Forms\Components\TextInput::make('grade')
                                ->label('Voto')
                                ->required()
                                ->maxLength(50)
                                ->placeholder('es. 8/10, B+, Ottimo...'),
                            \Filament\Forms\Components\Textarea::make('teacher_feedback')
                                ->label('Commento (opzionale)')
                                ->rows(3),
                        ];
                    })
                    ->action(function (Homework $r, array $data): void {
                        $sub = $r->submissions()->where('status', 'submitted')->first();
                        if (! $sub) return;
                        $sub->update([
                            'grade'            => $data['grade'],
                            'teacher_feedback' => $data['teacher_feedback'] ?? null,
                            'status'           => 'graded',
                            'graded_at'        => now(),
                        ]);
                        Notification::make()->title('Compito valutato!')->success()->send();
                    })
                    ->modalSubmitActionLabel('Salva valutazione'),

                Tables\Actions\EditAction::make()->label('')->iconButton(),
                Tables\Actions\DeleteAction::make()->label('')->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeacherHomeworks::route('/'),
            'create' => Pages\CreateTeacherHomework::route('/create'),
            'edit'   => Pages\EditTeacherHomework::route('/{record}/edit'),
        ];
    }
}
