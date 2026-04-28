<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeworkResource\Pages;
use App\Models\Contract;
use App\Models\Homework;
use App\Models\Student;
use App\Models\User;
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
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HomeworkResource extends Resource
{
    protected static ?string $model = Homework::class;

    protected static ?string $navigationIcon  = 'heroicon-o-inbox-arrow-down';
    protected static ?string $navigationGroup = 'Didattica';
    protected static ?string $navigationLabel = 'Esercitazioni con restituzione';
    protected static ?string $modelLabel      = 'Esercitazione';
    protected static ?string $pluralModelLabel = 'Esercitazioni con restituzione';
    protected static ?int    $navigationSort  = 6;

    public static function canAccess(): bool
    {
        return Auth::user()?->hasAnyRole(['superadmin', 'Amministrazione', 'Segreteria', 'admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── Step 1: Studente ──────────────────────────────────────────────
            Section::make('Studente')->schema([
                Select::make('student_search')
                    ->label('Cerca studente')
                    ->placeholder('Digita cognome o nome…')
                    ->options(fn (): array =>
                        Student::orderBy('last_name')->orderBy('first_name')
                            ->get()
                            ->mapWithKeys(fn (Student $s) => [
                                $s->id => trim($s->last_name . ' ' . $s->first_name)
                                    . ($s->email ? ' ‹' . $s->email . '›' : ''),
                            ])->toArray()
                    )
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        $state = $state ? (int) $state : null;
                        if (! $state) {
                            $set('contract_id', null);
                            $set('teacher_id', null);
                            $set('language', null);
                            return;
                        }

                        // Carica il contratto attivo dello studente (preferisce 'active')
                        $contract = Contract::whereHas('students', fn ($q) => $q->where('students.id', $state))
                            ->where('status', 'active')
                            ->latest('id')
                            ->first()
                            ?? Contract::whereHas('students', fn ($q) => $q->where('students.id', $state))
                                ->latest('id')
                                ->first();

                        if ($contract) {
                            $set('contract_id', $contract->id);

                            // Precompila docente dal pivot contract_students
                            $pivot = $contract->students()->where('students.id', $state)->first()?->pivot;
                            if ($pivot?->teacher_id) {
                                $set('teacher_id', $pivot->teacher_id);
                            }

                            // Precompila lingua
                            $langs = is_array($contract->languages) ? $contract->languages : [];
                            if (count($langs) === 1) {
                                $set('language', $langs[0]);
                            }
                        }
                    }),

                // Contratto (si precompila automaticamente, ma può essere cambiato)
                Select::make('contract_id')
                    ->label('Contratto')
                    ->live()
                    ->required()
                    ->searchable()
                    ->preload(false)
                    ->getSearchResultsUsing(fn (string $search): array =>
                        Contract::query()
                            ->where(fn ($q) => $q
                                ->where('billing_last_name', 'like', "%{$search}%")
                                ->orWhere('billing_first_name', 'like', "%{$search}%")
                            )
                            ->orderByDesc('id')
                            ->limit(20)
                            ->get()
                            ->mapWithKeys(fn ($c) => [
                                $c->id => self::contractLabel($c),
                            ])->toArray()
                    )
                    ->getOptionLabelUsing(fn ($v): ?string =>
                        $v ? self::contractLabel(Contract::find($v)) : null
                    )
                    ->helperText('Viene precompilato selezionando lo studente. Puoi cambiarlo manualmente.'),
            ]),

            // ── Step 2: Dettagli compito ──────────────────────────────────────
            Section::make('Dettagli esercitazione')->schema([
                Grid::make(2)->schema([
                    TextInput::make('title')
                        ->label('Titolo compito')
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

                Grid::make(2)->schema([
                    Select::make('teacher_id')
                        ->label('Docente assegnante')
                        ->options(fn () => User::query()
                            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['docente', 'Docente']))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                        )
                        ->nullable()
                        ->searchable()
                        ->default(fn () => Auth::id()),

                    DateTimePicker::make('due_at')
                        ->label('Scadenza consegna')
                        ->nullable()
                        ->native(false)
                        ->displayFormat('d/m/Y H:i'),
                ]),

                Textarea::make('instructions')
                    ->label('Istruzioni')
                    ->rows(4)
                    ->nullable()
                    ->placeholder('Descrivi cosa deve fare lo studente…'),
            ]),

            // ── Allegato opzionale ────────────────────────────────────────────
            Section::make('Allegato (opzionale)')
                ->description('Puoi allegare un file con la traccia del compito.')
                ->schema([
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
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state && is_string($state)) {
                                $set('attachment_name', basename($state));
                            }
                        })
                        ->nullable(),

                    \Filament\Forms\Components\Hidden::make('attachment_name'),
                ])->collapsible()->collapsed(),
        ]);
    }

    // ── Label leggibile per un contratto ─────────────────────────────────────
    private static function contractLabel(?Contract $c): string
    {
        if (! $c) return '—';
        $name = ($c->billing_type ?? 'private') === 'company'
            ? ($c->company_name ?? '—')
            : trim(($c->billing_last_name ?? '') . ' ' . ($c->billing_first_name ?? ''));
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

                Tables\Columns\TextColumn::make('student_name')
                    ->label('Studente')
                    ->getStateUsing(function (Homework $r): string {
                        // Cerca il nome dallo studente via pivot contract_students
                        $student = $r->contract?->students()->first();
                        if ($student) {
                            return trim($student->last_name . ' ' . $student->first_name);
                        }
                        // Fallback: billing del contratto
                        $c = $r->contract;
                        if (! $c) return '—';
                        return ($c->billing_type ?? 'private') === 'company'
                            ? ($c->company_name ?? '—')
                            : trim(($c->billing_last_name ?? '') . ' ' . ($c->billing_first_name ?? ''));
                    }),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Docente')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_at')
                    ->label('Scadenza')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn (Homework $r): string => $r->isPastDue() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Consegne')
                    ->getStateUsing(fn (Homework $r): string =>
                        $r->submissions()->count() . ' / ' .
                        $r->submissions()->where('status', 'graded')->count() . ' val.'
                    )
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creato')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('language')
                    ->label('Lingua')
                    ->options([
                        'Arabo'                  => 'Arabo',
                        'Francese'               => 'Francese',
                        'Inglese'                => 'Inglese',
                        'Spagnolo'               => 'Spagnolo',
                        'Tedesco'                => 'Tedesco',
                        'Italiano per stranieri' => 'Italiano per stranieri',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('submissions')
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

                Tables\Actions\EditAction::make()->label('')->iconButton(),
                Tables\Actions\DeleteAction::make()->label('')->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListHomeworks::route('/'),
            'create' => Pages\CreateHomework::route('/create'),
            'edit'   => Pages\EditHomework::route('/{record}/edit'),
        ];
    }
}
