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

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery()
            ->with([
                'student',
                'teacher',
                'contract.course',
                'originalLesson',
                'recoveryLesson',
            ]);

        $u = auth()->user();

        if ($u?->hasRole('Docente')) {
            $q->where('teacher_id', (int) $u->id);
        }

        return $q;
    }

    public static function form(Form $form): Form
    {
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

                    Select::make('teacher_id')
                        ->label('Docente')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            return User::query()
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
                                ->toArray();
                        })
                        ->live(),

                    DateTimePicker::make('starts_at')
                        ->label('Inizio')
                        ->required()
                        ->seconds(false)
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
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
                        ->live(),

                    Select::make('status_virtual')
                        ->label('Stato')
                        ->options([
                            'programmata'       => 'Programmata',
                            'completata'        => 'Completata',
                            'annullata_recover' => 'Annullata (da recuperare)',
                            'annullata'         => 'Annullata',
                            'recuperata'        => 'Recuperata',
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
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
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
                        ->visible(fn (Get $get) => in_array($get('status_virtual'), ['annullata', 'annullata_recover'], true)),

                    Textarea::make('cancellation_reason')
                        ->label('Motivo annullamento')
                        ->rows(3)
                        ->visible(fn (Get $get) => in_array($get('status_virtual'), ['annullata', 'annullata_recover'], true)),

                    Placeholder::make('recovery_info')
                        ->label('Info recupero')
                        ->content(function (?Lesson $record) {
                            if (! $record) return '—';

                            // se è recupero
                            if ($record->recovery_of_lesson_id) {
                                $orig = $record->originalLesson;
                                if (! $orig) return 'Recupero (originale non trovato)';
                                return 'Recupero di: ' . ($orig->starts_at?->format('d/m/Y H:i') ?? '—');
                            }

                            // se è originale annullata con recupero
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
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('Note')
                        ->rows(4)
                        ->columnSpanFull(),

                    Placeholder::make('consumption_preview')
                        ->label('Ore (durata)')
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

                Tables\Columns\TextColumn::make('teacher_last_name')
    ->label('Docente')
    ->getStateUsing(function (Lesson $record) {
        $t = $record->teacher;
        if (! $t) return '—';

        // priorità: last_name
        $last = trim((string) ($t->last_name ?? ''));
        if ($last !== '') return mb_strtoupper($last);

        // fallback: prova a derivarlo da "name"
        $name = trim((string) ($t->name ?? ''));
        if ($name !== '') {
            $parts = preg_split('/\s+/', $name);
            $maybeLast = trim((string) (end($parts) ?: ''));
            return $maybeLast !== '' ? mb_strtoupper($maybeLast) : $name;
        }

        // fallback finale
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

                Tables\Columns\TextColumn::make('originalLesson.starts_at')
    ->label('Recupero di')
    ->dateTime('d/m/Y H:i')
    ->placeholder('—')
    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('recoveryLesson.starts_at')
    ->label('Recupero programmato')
    ->dateTime('d/m/Y H:i')
    ->placeholder('—')
    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_auto_recovery')
                    ->label('Auto')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cancelled_at')
                    ->label('Annullata il')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cancellation_reason')
                    ->label('Motivo')
                    ->limit(30)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status_virtual')
                    ->label('Stato')
                    ->getStateUsing(function (Lesson $record) {
                        if ($record->recovery_of_lesson_id) {
                            return 'recuperata';
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
                        'annullata_recover' => 'Annullata (da recuperare)',
                        'annullata'         => 'Annullata',
                        'recuperata'        => 'Recuperata',
                        default             => ucfirst((string) $state),
                    }),
            ])
            ->filters([
                Filter::make('upcoming')
                    ->label('Da oggi')
                    ->default()
                    ->query(fn (Builder $query): Builder =>
                        $query->where('starts_at', '>=', Carbon::today()->startOfDay())
                    ),

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
                        'annullata_recover' => 'Annullata (da recuperare)',
                        'annullata'         => 'Annullata',
                        'recuperata'        => 'Recuperata',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if (! $value) return $query;

                        return match ($value) {
                            'recuperata' => $query->whereNotNull('recovery_of_lesson_id'),
                            'annullata' => $query->whereNull('recovery_of_lesson_id')->whereNotNull('cancelled_at')->where('is_recoverable', false),
                            'annullata_recover' => $query->whereNull('recovery_of_lesson_id')->whereNotNull('cancelled_at')->where('is_recoverable', true),
                            'completata' => $query->whereNull('cancelled_at')->whereNull('recovery_of_lesson_id')->where('counts_as_consumed', true),
                            'programmata' => $query->whereNull('cancelled_at')->whereNull('recovery_of_lesson_id')->where('counts_as_consumed', false),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifica'),

                Action::make('mark_done')
                    ->label('Svolta')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function (Lesson $record): bool {
                        if ($record->cancelled_at) return false;
                        return ! (bool) $record->counts_as_consumed;
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

                Action::make('unmark_done')
                    ->label('Togli svolta')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(function (Lesson $record): bool {
                        if ($record->cancelled_at) return false;

                        $u = auth()->user();
                        $can = $u?->hasAnyRole(['superadmin', 'amministrazione', 'segreteria']) ?? false;

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
                    ->modalDescription('Se annulli almeno 24h prima, la lezione sarà “da recuperare” e verrà creata automaticamente una nuova lezione la settimana successiva all’ultima lezione schedulata (saltando i giorni di chiusura).')
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
                    ->visible(function (Lesson $record): bool {
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

                        if ($record->is_recoverable) {
                            try {
                                $recovery = app(LessonRecoveryService::class)
                                    ->cancelAndCreateAutoRecovery($record, $cancelledAt, $reason);

                                $body = 'Recupero programmato per: ' . $recovery->starts_at->format('d/m/Y H:i');

                                $movedReason = $recovery->getAttribute('_moved_reason');
                                if ($movedReason) {
                                    $body .= "\n" . $movedReason;
                                }

                                Notification::make()
                                    ->title('Lezione annullata e recupero creato')
                                    ->body($body)
                                    ->success()
                                    ->send();

                                return;
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('Lezione annullata, ma recupero NON creato')
                                    ->body($e->getMessage())
                                    ->warning()
                                    ->send();

                                return;
                            }
                        }

                        Notification::make()
                            ->title('Lezione annullata (non recuperabile)')
                            ->body('Annullata entro 24h: secondo regola, consuma.')
                            ->success()
                            ->send();
                    }),

                Action::make('open_recovery')
                    ->label('Apri recupero')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->visible(fn (Lesson $record) => $record->recoveryLesson()->exists())
                    ->url(fn (Lesson $record) => static::getUrl('edit', ['record' => $record->recoveryLesson->id]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Elimina')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessons::route('/'),
            'edit'  => Pages\EditLesson::route('/{record}/edit'),
        ];
    }
}
