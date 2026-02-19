<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Filament\Resources\LessonResource;
use App\Models\Lesson;
use App\Models\User;
use App\Services\LessonRecoveryService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    protected static ?string $title = 'Lezioni';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'asc')
            ->modifyQueryUsing(function (Builder $query) {
                // ottimizza e allinea comportamento alla lista Lezioni
                $query->with(['teacher', 'contract.course', 'recoveryLesson', 'originalLesson']);

                $u = auth()->user();
                if ($u?->hasRole('Docente')) {
                    $query->where('teacher_id', (int) $u->id);
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Inizio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('contract.course.name')
                    ->label('Corso')
                    ->placeholder('—')
                    ->limit(18)
                    ->wrap()
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher_label')
                    ->label('Docente')
                    ->getStateUsing(function (Lesson $record) {
                        $t = $record->teacher;
                        if (! $t) return '—';
                        return trim((string) ($t->name ?? ''))
                            ?: trim(($t->first_name ?? '') . ' ' . ($t->last_name ?? ''))
                            ?: ($t->email ?? ('Docente #' . $t->id));
                    })
                    ->limit(18)
                    ->wrap(),

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
                // ✅ default: da oggi
                Filter::make('upcoming')
                    ->label('Da oggi')
                    ->default()
                    ->query(fn (Builder $query) => $query->where('starts_at', '>=', Carbon::today()->startOfDay())),

                // ✅ range date
                Filter::make('date_range')
                    ->label('Date')
                    ->form([
                        DatePicker::make('from')->label('Da'),
                        DatePicker::make('until')->label('A'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $from) => $q->whereDate('starts_at', '>=', $from))
                            ->when($data['until'] ?? null, fn (Builder $q, $until) => $q->whereDate('starts_at', '<=', $until));
                    }),

                // ✅ stato
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

                // ✅ docente (utile per segreteria)
                SelectFilter::make('teacher_id')
                    ->label('Docente')
                    ->options(fn () => User::query()
                        ->role('Docente')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    ),
            ])
            ->actions([
                // ✅ Apri
                Action::make('apri')
                    ->label('Apri')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (Lesson $record) => LessonResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),

                // ✅ Segna svolta
                Action::make('mark_done')
                    ->label('Segna svolta')
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

                // ✅ Togli svolta
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

                // ✅ Annulla + auto recupero (stessa logica della lista Lezioni)
                Action::make('cancel')
                    ->label('Annulla')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Annulla lezione')
                    ->modalDescription('Se annulli almeno 24h prima, la lezione sarà “da recuperare” e verrà creata automaticamente una nuova lezione nel primo giorno utile.')
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

                // ✅ Apri recupero (se esiste)
                Action::make('open_recovery')
                    ->label('Apri recupero')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->visible(fn (Lesson $record) => $record->recoveryLesson()->exists())
                    ->url(fn (Lesson $record) => LessonResource::getUrl('edit', ['record' => $record->recoveryLesson->id]))
                    ->openUrlInNewTab(),
            ]);
    }
}
