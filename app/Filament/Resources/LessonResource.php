<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasAreaPermission;
use App\Filament\Resources\LessonResource\Pages;
use App\Models\Lesson;
use App\Models\User;
use App\Services\LessonRecoveryService;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Filament\Facades\Filament;
use Filament\Tables\Actions\ActionGroup;



class LessonResource extends Resource
{
    use HasAreaPermission;

    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Didattica';
    protected static ?string $navigationLabel = 'Lezioni';
    protected static ?string $modelLabel = 'Lezione';
    protected static ?string $pluralModelLabel = 'Lezioni';
    protected static ?int $navigationSort = 2;

protected static function isTeacherPanel(): bool
{
    $id = Filament::getCurrentPanel()?->getId();
    return is_string($id) && strcasecmp($id, 'Docente') === 0; // ✅ Docente/docente
}

protected static function isTableFilterActive(array $data): bool
{
    return filter_var($data['isActive'] ?? false, FILTER_VALIDATE_BOOLEAN);
}








public static function getEloquentQuery(): Builder
{
    $q = parent::getEloquentQuery();

    // ✅ Eager load leggero (solo ciò che serve in lista)
$q->with([
    'student:id,first_name,last_name',
    'teacher:id,name,first_name,last_name,email',
    'contract:id,course_id,language_id',   // ✅ aggiungi language_id
    'contract.course:id,name',
]);

    if (Filament::getCurrentPanel()?->getId() === 'Docente') {
    $teacherId = (int) auth()->id();

    $q->where(function (Builder $qq) use ($teacherId) {
        // 1) lezioni mie
        $qq->where('teacher_id', $teacherId)

           // 2) storico lezioni di studenti che OGGI sono assegnati a me su un contratto
           ->orWhereHas('contract.students', function (Builder $q2) use ($teacherId) {
               $q2->wherePivot('teacher_id', $teacherId);
           });
    });
}

    return $q;
}

    public static function form(Form $form): Form
    {
        $isTeacher = static::isTeacherPanel();

        return $form->schema([
            Section::make('Dettagli lezione')
                ->columns(3)
                ->schema([
                    Hidden::make('counts_as_consumed'),
                    Hidden::make('is_recoverable'),
                    Hidden::make('recovery_of_lesson_id'),
                    Hidden::make('is_auto_recovery'),

                    Placeholder::make('student_label')
                        ->label('Studente')
                        ->content(function (?Lesson $record) {
                            if (! $record) return '—';

                            $name = $record->contractStudent?->beneficiary_full_name;
                            if (! blank($name)) return $name;

                            $s = $record->student;
                            if (! $s) return '—';

                            return $s->full_name
                                ?? trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''))
                                ?: '—';
                        }),

                    Placeholder::make('course_label')
                        ->label('Corso')
                        ->content(fn (?Lesson $record) => $record?->contract?->course?->name ?? '—'),

                    Placeholder::make('hours_remaining')
                        ->label('Ore rimanenti (contratto)')
                        ->visible(! $isTeacher) // nel panel docente non serve
                        ->content(function (?Lesson $record) {
                            $c = $record?->contract;
                            if (! $c) return '—';

                            $purchased = (float) ($c->hours_purchased ?? 0);
                            $consumed  = (float) ($c->hours_consumed ?? 0);
                            $remaining = max(0, $purchased - $consumed);

                            $fmt = fmod($remaining, 1.0) === 0.0
                                ? (string) (int) $remaining
                                : rtrim(rtrim(number_format($remaining, 2, ',', ''), '0'), ',');

                            return "Residue: {$fmt}";
                        }),

Select::make('language_id')
    ->label('Lingua lezione')
    ->options(function (Get $get, ?\App\Models\Lesson $record) {
        // prendo contract_id dal record o dallo state
        $contractId = $record?->contract_id ?: $get('contract_id');

        $all = \App\Filament\Resources\ContractResource::subjectOptions();

        if (!$contractId) {
            return $all;
        }

        $contract = \App\Models\Contract::find($contractId);
        if (!$contract) {
            return $all;
        }

        $langs = $contract->languages ?? [];
        $langs = array_values(array_filter($langs));

        if (empty($langs)) {
            return $all;
        }

        // mappa solo le lingue presenti nel contratto
        return collect($all)
            ->only($langs)
            ->toArray();
    })
    ->searchable()
    ->required()
    ->default(function (Get $get, ?\App\Models\Lesson $record) {
        if ($record?->language_id) return $record->language_id;

        $contractId = $get('contract_id');
        if (!$contractId) return null;

        return \App\Models\Contract::whereKey($contractId)->value('language_id'); // prima lingua
    })
    ->helperText('Puoi modificarla in qualsiasi momento.'),


                        Select::make('teacher_id')
    ->label('Docente')
    ->nullable()
    ->searchable()
    ->disabled($isTeacher)
    ->preload(false) // ✅ niente preload
    ->getSearchResultsUsing(fn (string $search) => User::query()
        ->role('Docente')
        ->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%");
        })
        ->orderBy('name')
        ->limit(50)
        ->get()
        ->mapWithKeys(function (User $u) {
            $label = trim((string) ($u->name ?? ''));
            if ($label === '') $label = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
            if ($label === '') $label = $u->email ?: ('Docente #' . $u->id);
            return [$u->id => $label];
        })
        ->toArray()
    )
    ->getOptionLabelUsing(function ($value): ?string {
        $u = User::find($value);
        if (! $u) return null;
        $label = trim((string) ($u->name ?? ''));
        if ($label === '') $label = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
        return $label !== '' ? $label : ($u->email ?: ('Docente #' . $u->id));
    })
    ->live(),

                    DateTimePicker::make('starts_at')
                        ->label('Inizio')
                        ->required()
                        ->seconds(false)
                        ->disabled($isTeacher)
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) use ($isTeacher) {
                            if ($isTeacher) return;

                            $start = $get('starts_at');
                            if (! $start) return;

                            if (! $get('ends_at')) {
                                $set('ends_at', Carbon::parse($start)->copy()->addMinutes(60));
                            }
                        }),

                    DateTimePicker::make('ends_at')
                        ->label('Fine')
                        ->required()
                        ->seconds(false)
                        ->disabled($isTeacher)
                        ->live(),

                    // Stato: nel panel docente solo lettura (disabled + opzionale hidden)
                    Select::make('status_virtual')
                        ->label('Stato')
                        ->disabled($isTeacher)
                        ->options([
                            'programmata'       => 'Programmata',
                            'completata'        => 'Completata',
                            'annullata_recover' => 'Annullata (recupero)',
                            'annullata'         => 'Annullata',
                            'recuperata'        => 'Da recuperare',
                        ])
                        ->dehydrated(false)
                        ->live()
                        ->afterStateHydrated(function (Set $set, ?Lesson $record) {
                            if (! $record) return;

                            if ($record->recovery_of_lesson_id) {
                                $set('status_virtual', 'recuperata');
                                return;
                            }

                            if ($record->cancelled_at) {
                                $set('status_virtual', $record->is_recoverable ? 'annullata_recover' : 'annullata');
                                return;
                            }

                            $set('status_virtual', $record->counts_as_consumed ? 'completata' : 'programmata');
                        })
                        ->afterStateUpdated(function (Get $get, Set $set, $state) use ($isTeacher) {
                            if ($isTeacher) return;

                            if ($state === 'recuperata') return;

                            if (in_array($state, ['annullata', 'annullata_recover'], true)) {
                                if (! $get('cancelled_at')) $set('cancelled_at', now());
                                $set('is_recoverable', $state === 'annullata_recover');
                                $set('counts_as_consumed', false);
                                return;
                            }

                            if ($state === 'completata') {
                                $set('cancelled_at', null);
                                $set('cancellation_reason', null);
                                $set('is_recoverable', false);
                                $set('counts_as_consumed', true);
                                return;
                            }

                            // programmata
                            $set('cancelled_at', null);
                            $set('cancellation_reason', null);
                            $set('is_recoverable', false);
                            $set('counts_as_consumed', false);
                        }),

                    DateTimePicker::make('cancelled_at')
                        ->label('Annullata il')
                        ->seconds(false)
                        ->visible(fn (Get $get) => ! $isTeacher && in_array($get('status_virtual'), ['annullata', 'annullata_recover'], true)),

                    Textarea::make('cancellation_reason')
                        ->label('Motivo annullamento')
                        ->rows(3)
                        ->visible(fn (Get $get) => ! $isTeacher && in_array($get('status_virtual'), ['annullata', 'annullata_recover'], true)),

                    Placeholder::make('recovery_info')
                        ->label('Info recupero')
                        ->visible(! $isTeacher)
                        ->content(function (?Lesson $record) {
                            if (! $record) return '—';

                            if ($record->recovery_of_lesson_id) {
                                $orig = $record->originalLesson;
                                if (! $orig) return 'Recupero (originale non trovato)';
                                return 'Recupero di: ' . ($orig->starts_at?->format('d/m/Y H:i') ?? '—');
                            }

                            $rec = $record->recoveryLesson;
                            if ($rec) {
                                $auto = $rec->is_auto_recovery ? ' (auto)' : '';
                                return 'Recupero programmato: ' . ($rec->starts_at?->format('d/m/Y H:i') ?? '—') . $auto;
                            }

                            return '—';
                        })
                        ->columnSpanFull(),

                    TextInput::make('meet_url')
                        ->label('Google Meet URL')
                        ->url()
                        ->maxLength(500)
                        ->disabled($isTeacher)
                        ->columnSpanFull(),

                    // ✅ Docente: può compilare
                    Textarea::make('notes')
                        ->label($isTeacher ? 'Note docente' : 'Note')
                        ->rows(4)
                        ->columnSpanFull(),

                    Textarea::make('homework')
                        ->label('Compiti per casa')
                        ->rows(4)
                        ->columnSpanFull(),

                    Placeholder::make('consumption_preview')
                        ->label('Ore (durata)')
                        ->visible(! $isTeacher)
                        ->content(function (Get $get) {
                            $start = $get('starts_at');
                            $end   = $get('ends_at');

                            if (! $start || ! $end) return '—';

                            $mins = Carbon::parse($start)->diffInMinutes(Carbon::parse($end), false);
                            if ($mins <= 0) return '⚠️ La fine deve essere dopo l’inizio.';

                            $hours = (int) ceil($mins / 60);
                            return "Durata: {$hours} ore.";
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $isTeacher = static::isTeacherPanel();

        return $table
            ->defaultSort('starts_at', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Inizio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('Studente')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $search = trim($search);

                        return $query->whereHas('student', function (Builder $q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('course_label')
                    ->label('Corso')
                    ->limit(15)
                    ->getStateUsing(fn (Lesson $record) => $record->contract?->course?->name ?? '—')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->leftJoin('contracts', 'lessons.contract_id', '=', 'contracts.id')
                            ->leftJoin('courses', 'contracts.course_id', '=', 'courses.id')
                            ->orderBy('courses.name', $direction)
                            ->select('lessons.*');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $search = trim($search);

                        return $query->whereHas('contract.course', function (Builder $q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }),


    Tables\Columns\TextColumn::make('language_label')
    ->label('Lingua')
    ->getStateUsing(function (Lesson $record) {
        return $record->language_id
            ?? $record->contract?->language_id
            ?? '—';
    })
    ->sortable()
    ->toggleable(),

                Tables\Columns\TextColumn::make('teacher_last_name')
                    ->label('Docente')
                    ->getStateUsing(function (Lesson $record) {
                        $t = $record->teacher;
                        if (! $t) return '—';

                        $last = trim((string) ($t->last_name ?? ''));
                        if ($last !== '') return mb_strtoupper($last);

                        $name = trim((string) ($t->name ?? ''));
                        if ($name !== '') {
                            $parts = preg_split('/\s+/', $name);
                            $maybeLast = trim((string) (end($parts) ?: ''));
                            return $maybeLast !== '' ? mb_strtoupper($maybeLast) : $name;
                        }

                        return $t->email ? $t->email : ('Docente #' . $t->id);
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->leftJoin('users as t', 'lessons.teacher_id', '=', 't.id')
                            ->orderByRaw("COALESCE(NULLIF(t.last_name,''), t.name) {$direction}")
                            ->select('lessons.*');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $search = trim($search);

                        return $query->whereHas('teacher', function (Builder $q) use ($search) {
                            $q->where('last_name', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%");
                        });
                    }),

                Tables\Columns\BadgeColumn::make('status_virtual')
                    ->label('Stato')
                    ->getStateUsing(function (Lesson $record) {
    // ✅ RECUPERO: se svolta -> completata, altrimenti da recuperare
    if ($record->recovery_of_lesson_id) {
        return $record->counts_as_consumed ? 'completata' : 'recuperata';
    }

    if ($record->cancelled_at) {
        return $record->is_recoverable ? 'annullata_recover' : 'annullata';
    }

    return $record->counts_as_consumed ? 'completata' : 'programmata';
})
                    ->colors([
                        'success' => 'completata',
                        'danger'  => 'annullata',
                        'warning' => 'programmata',
                        'gray'    => 'annullata_recover',
                        'info'    => 'recuperata',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'programmata'       => 'Programmata',
                        'completata'        => 'Completata',
                        'annullata_recover' => 'Annullata (recupero)',
                        'annullata'         => 'Annullata',
                        'recuperata'        => 'Da Recuperare',
                        default             => ucfirst((string) $state),
                    }),



            ])
            ->filters([
                SelectFilter::make('student_id')
                    ->label('Studente')
                    ->options(fn () => \App\Models\Student::orderBy('last_name')->orderBy('first_name')
                        ->get()
                        ->mapWithKeys(fn ($s) => [$s->id => trim($s->last_name . ' ' . $s->first_name)])
                        ->toArray()
                    )
                    ->searchable()
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $id) => $q->where('lessons.student_id', $id)
                    )),

                SelectFilter::make('academic_year')
                    ->label('Anno didattico')
                    ->options(fn () => \App\Models\Contract::query()
                        ->whereNotNull('academic_year')
                        ->distinct()
                        ->orderBy('academic_year')
                        ->pluck('academic_year', 'academic_year')
                        ->toArray()
                    )
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $year) => $q->whereHas('contract', fn (Builder $cq) => $cq->where('academic_year', $year))
                    )),

Filter::make('upcoming')
    ->label('Da oggi')
    ->default()
    ->query(function (Builder $query, array $data): Builder {
        if (! static::isTableFilterActive($data)) return $query;
        return $query->where('lessons.starts_at', '>=', Carbon::today()->startOfDay());
    }),

Filter::make('missing_notes')
    ->label('Senza note')
    ->query(function (Builder $q, array $data): Builder {
        if (! static::isTableFilterActive($data)) return $q;
        return $q->whereRaw("(lessons.notes IS NULL OR lessons.notes = '')");
    }),

Filter::make('missing_homework')
    ->label('Senza compiti')
    ->query(function (Builder $q, array $data): Builder {
        if (! static::isTableFilterActive($data)) return $q;
        return $q->whereRaw("(lessons.homework IS NULL OR lessons.homework = '')");
    }),

                Filter::make('date_range')
                    ->label('Date')
                    ->form([
                        DatePicker::make('from')->label('Da'),
                        DatePicker::make('until')->label('A'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $from) =>
                                $q->whereDate('starts_at', '>=', $from)
                            )
                            ->when($data['until'] ?? null, fn (Builder $q, $until) =>
                                $q->whereDate('starts_at', '<=', $until)
                            );
                    }),

                SelectFilter::make('status_virtual')
                    ->label('Stato')
                    ->options([
                        'programmata'       => 'Programmata',
                        'completata'        => 'Completata',
                        'annullata_recover' => 'Annullata (recupero)',
                        'annullata'         => 'Annullata',
                        'recuperata'        => 'Da Recuperare',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if (! $value) return $query;

                        return match ($value) {
                            'recuperata' => $query->whereNotNull('lessons.recovery_of_lesson_id'),
                            'annullata' => $query->whereNull('lessons.recovery_of_lesson_id')->whereNotNull('lessons.cancelled_at')->where('lessons.is_recoverable', false),
                            'annullata_recover' => $query->whereNull('lessons.recovery_of_lesson_id')->whereNotNull('lessons.cancelled_at')->where('lessons.is_recoverable', true),
                            'completata' => $query->whereNull('lessons.cancelled_at')->whereNull('lessons.recovery_of_lesson_id')->where('lessons.counts_as_consumed', true),
                            'programmata' => $query->whereNull('lessons.cancelled_at')->whereNull('lessons.recovery_of_lesson_id')->where('lessons.counts_as_consumed', false),
                            default => $query,
                        };
                    }),

                SelectFilter::make('anomaly')
                    ->label('Anomalia')
                    ->options([
                        'no_duration' => 'Senza durata esplicita',
                        'long_duration' => 'Durata superiore a 2 ore',
                        'no_recovery_planned' => 'Da recuperare senza recupero pianificato',
                        'future_completed' => 'Future già completate',
                        'past_unmanaged' => 'Passate non gestite',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if (! filled($value)) {
                            return $query;
                        }

                        return match ($value) {
                            'no_duration' => $query
                                ->whereNull('lessons.cancelled_at')
                                ->whereRaw('(lessons.duration_minutes IS NULL OR lessons.duration_minutes <= 0)')
                                ->whereNull('lessons.ends_at'),

                            'long_duration' => $query
                                ->whereNull('lessons.cancelled_at')
                                ->whereRaw('(
                                    lessons.duration_minutes > 120
                                    OR (
                                        lessons.ends_at IS NOT NULL
                                        AND lessons.starts_at IS NOT NULL
                                        AND TIMESTAMPDIFF(MINUTE, lessons.starts_at, lessons.ends_at) > 120
                                    )
                                )'),

                            'no_recovery_planned' => $query
                                ->where('lessons.is_recoverable', true)
                                ->whereNotNull('lessons.cancelled_at')
                                ->whereNull('lessons.recovery_of_lesson_id')
                                ->whereNotExists(function ($sub) {
                                    $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                                        ->from('lessons as r')
                                        ->whereColumn('r.recovery_of_lesson_id', 'lessons.id');
                                }),

                            'future_completed' => $query
                                ->whereNotNull('lessons.completed_at')
                                ->where('lessons.starts_at', '>', now()),

                            'past_unmanaged' => $query
                                ->whereNull('lessons.completed_at')
                                ->whereNull('lessons.cancelled_at')
                                ->where('lessons.starts_at', '<', now()->subHours(2))
                                ->whereNull('lessons.recovery_of_lesson_id'),

                            default => $query,
                        };
                    }),

            ])
                    ->paginated([25, 50])
        ->defaultPaginationPageOption(25)
           ->actions([

    // ✏️ Modifica come icona compatta
    Tables\Actions\EditAction::make()
        ->label('')
        ->icon('heroicon-o-pencil-square')
        ->iconButton()
->visible(function (Lesson $record) use ($isTeacher) {
    if ($isTeacher) {
        return (int) $record->teacher_id === (int) auth()->id();
    }
    return static::canEdit($record);
}),

    // ✅ Svolta come icona compatta
    Action::make('mark_done')
        ->label('')
        ->tooltip('Segna come svolta')
        ->icon('heroicon-o-check-circle')
        ->iconButton()
        ->color('success')
        ->requiresConfirmation()
        ->visible(function (Lesson $record) use ($isTeacher): bool {
            if ($record->cancelled_at) return false;
            if ((bool) $record->counts_as_consumed) return false;

            $u = auth()->user();
            if ($u?->hasAnyRole(['superadmin', 'Amministrazione', 'Segreteria'])) {
                return true;
            }

            return $isTeacher && ((int) $record->teacher_id === (int) auth()->id());
        })
        ->action(function (Lesson $record): void {
            $record->counts_as_consumed = true;
            $record->is_recoverable = false;
            $record->save();

            Notification::make()
                ->title('Lezione segnata come svolta')
                ->success()
                ->send();
        }),

    // ⋯ Menu compatto con tutte le altre azioni
    ActionGroup::make([

    Action::make('view_notes')
    ->label('Leggi note')
    ->icon('heroicon-o-eye')
    ->modalHeading('Note e compiti')
    ->form([
        Textarea::make('notes')->label('Note docente')->rows(6)->disabled(),
        Textarea::make('homework')->label('Compiti')->rows(6)->disabled(),
    ])
    ->fillForm(fn (Lesson $record) => [
        'notes' => $record->notes,
        'homework' => $record->homework,
    ])
    ->visible(function () use ($isTeacher) {
        if ($isTeacher) return false;
        return auth()->user()?->hasAnyRole(['superadmin', 'Amministrazione', 'Segreteria']) ?? false;
    }),

        Action::make('quick_notes')
            ->label('Note')
            ->icon('heroicon-o-pencil-square')
            ->modalHeading('Note docente')
            ->form([
                Textarea::make('notes')->label('Note docente')->rows(6),
            ])
            ->fillForm(fn (Lesson $record) => ['notes' => $record->notes])
            ->visible(fn (Lesson $record) => $isTeacher
    ? ((int) $record->teacher_id === (int) auth()->id())
    : static::canEdit($record)
)
            ->action(function (Lesson $record, array $data): void {
                $record->notes = $data['notes'] ?? null;
                $record->save();
                Notification::make()->title('Note salvate')->success()->send();
            }),

        Action::make('quick_homework')
            ->label('Compiti')
            ->icon('heroicon-o-document-text')
            ->modalHeading('Compiti per casa')
            ->form([
                Textarea::make('homework')->label('Compiti per casa')->rows(6),
            ])
            ->fillForm(fn (Lesson $record) => ['homework' => $record->homework])
->visible(fn (Lesson $record) => $isTeacher
    ? ((int) $record->teacher_id === (int) auth()->id())
    : static::canEdit($record)
)
            ->action(function (Lesson $record, array $data): void {
                $record->homework = $data['homework'] ?? null;
                $record->save();
                Notification::make()->title('Compiti salvati')->success()->send();
            }),

        Action::make('unmark_done')
            ->label('Togli svolta')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->requiresConfirmation()
            ->visible(function (Lesson $record) use ($isTeacher): bool {
                if ($isTeacher) return false;
                if ($record->cancelled_at) return false;

                $u = auth()->user();
                $can = $u?->hasAnyRole(['superadmin', 'Amministrazione', 'Segreteria']) ?? false;

                return $can && (bool) $record->counts_as_consumed;
            })
            ->action(function (Lesson $record): void {
                $record->counts_as_consumed = false;
                $record->save();

                Notification::make()
                    ->title('Flag svolta rimosso')
                    ->warning()
                    ->send();
            }),

        Action::make('cancel')
            ->label('Annulla')
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->requiresConfirmation()
            ->modalHeading('Annulla lezione')
            ->form([
                DateTimePicker::make('cancelled_at')
                    ->label('Data e ora annullamento')
                    ->seconds(false)
                    ->default(fn () => now())
                    ->required(),

                Textarea::make('cancellation_reason')
                    ->label('Motivo annullamento')
                    ->rows(3)
                    ->required(),
            ])
            ->visible(function (Lesson $record) use ($isTeacher): bool {
                if ($isTeacher) return false;
                if ($record->cancelled_at) return false;
                if ($record->counts_as_consumed) return false;
                if ($record->recovery_of_lesson_id) return false;
                return true;
            })
->action(function (Lesson $record, array $data): void {
    $cancelledAt = Carbon::parse($data['cancelled_at']);
    $reason = (string) ($data['cancellation_reason'] ?? '');

    $record->cancelled_at = $cancelledAt;
    $record->cancelled_by = auth()->id();
    $record->cancellation_reason = $reason;

    $record->recomputeFlags($cancelledAt);

    if ($record->is_recoverable) {
        $record->counts_as_consumed = false;
    }

    $record->save();

    // ✅ CREA RECUPERO AUTOMATICO (se recuperabile)
    if ($record->is_recoverable) {
        // evita duplicati
        if (! $record->recoveryLesson()->exists()) {
            try {
                $recovery = app(\App\Services\LessonRecoveryService::class)
                    ->cancelAndCreateAutoRecovery($record, $cancelledAt, $reason);

                Notification::make()
                    ->title('Lezione annullata + recupero creato')
                    ->body('Recupero: ' . $recovery->starts_at->format('d/m/Y H:i'))
                    ->success()
                    ->send();

                return;
            } catch (\Throwable $e) {
                Notification::make()
                    ->title('Lezione annullata, ma recupero non creato')
                    ->body($e->getMessage())
                    ->warning()
                    ->send();

                return;
            }
        }
    }

    Notification::make()
        ->title('Lezione annullata')
        ->success()
        ->send();
}),

        Action::make('create_recovery')
            ->label('Crea recupero')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Crea lezione di recupero')
            ->modalDescription('Verrà creata automaticamente una lezione di recupero nella prima settimana dopo la fine delle altre lezioni del contratto, nello stesso giorno e orario, spostandola alla prima settimana libera se necessario in caso di chiusura o docente occupato.')
            ->visible(function (Lesson $record) use ($isTeacher): bool {
                if ($isTeacher) return false;
                if (! $record->cancelled_at) return false;
                if (! $record->is_recoverable) return false;
                if ($record->recovery_of_lesson_id) return false;

                return ! $record->recoveryLesson()->exists();
            })
            ->action(function (Lesson $record): void {
                try {
                    $recovery = app(LessonRecoveryService::class)
                        ->cancelAndCreateAutoRecovery(
                            $record,
                            Carbon::parse($record->cancelled_at),
                            (string) ($record->cancellation_reason ?? '')
                        );

                    Notification::make()
                        ->title('Recupero creato')
                        ->body('Recupero: ' . $recovery->starts_at->format('d/m/Y H:i'))
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Recupero non creato')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            }),

        Action::make('open_recovery')
            ->label('Apri recupero')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('info')
            ->visible(fn (Lesson $record) => ! $isTeacher && $record->recoveryLesson()->exists())
            ->url(fn (Lesson $record) => static::getUrl('edit', ['record' => $record->recoveryLesson()->first()?->id]))
            ->openUrlInNewTab(),

    ])
        ->label('')
        ->icon('heroicon-o-ellipsis-horizontal')
        ->iconButton(),

])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Sposta nel cestino')
                        ->visible(fn () => ! $isTeacher)
                        ->requiresConfirmation()
                        ->modalHeading('Elimina lezioni selezionate')
                        ->modalDescription('Le lezioni selezionate verranno spostate nel cestino. Le lezioni già svolte non possono essere eliminate. Sei sicuro di voler continuare?')
                        ->modalSubmitActionLabel('Sì, elimina')
                        ->modalCancelActionLabel('Annulla')
                        ->before(function ($records) {
                            $hasDone = $records->contains(fn (Lesson $l) => (bool) $l->counts_as_consumed);

                            if ($hasDone) {
                                Notification::make()
                                    ->title('Operazione non consentita')
                                    ->body('Non puoi eliminare lezioni già svolte.')
                                    ->danger()
                                    ->send();

                                throw new \Exception('Non puoi eliminare lezioni già svolte.');
                            }
                        }),
                ]),
            ]);
    }


    public static function shouldRegisterNavigation(): bool
{
    // ✅ nel panel docente vogliamo SEMPRE la voce in sidebar
    if (Filament::getCurrentPanel()?->getId() === 'docente') {
        return true;
    }

    return parent::shouldRegisterNavigation();
}

public static function canViewAny(): bool
{
    // ✅ nel panel docente può vedere la lista (poi la query filtra)
    if (Filament::getCurrentPanel()?->getId() === 'Docente') {
        return true;
    }

    return parent::canViewAny();
}

public static function canEdit($record): bool
{
    if (Filament::getCurrentPanel()?->getId() === 'Docente') {
        return (int) $record->teacher_id === (int) auth()->id();
    }

    return parent::canEdit($record);
}

    public static function getPages(): array
{
    return [
        'index' => Pages\ListLessons::route('/'),
        'view'  => Pages\ViewLesson::route('/{record}'),
        'edit'  => Pages\EditLesson::route('/{record}/edit'),
    ];
}
}
