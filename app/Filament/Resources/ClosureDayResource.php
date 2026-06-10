<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClosureDayResource\Pages;
use App\Models\ClosureDay;
use App\Models\Contract;
use App\Models\Lesson;
use App\Services\LessonGeneratorService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class ClosureDayResource extends Resource
{
    protected static ?string $model = ClosureDay::class;

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';
    protected static ?string $navigationGroup = 'Didattica';
    protected static ?string $navigationLabel = 'Giorni di chiusura';
    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Chiusura')
                ->columns(2)
                ->schema([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Da')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            // Se end_date è vuota, la auto-compilo con start_date
                            // (resta comunque modificabile manualmente)
                            if (blank($get('end_date'))) {
                                $set('end_date', $state);
                            }
                        }),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('A')
                        ->helperText('Se lasci vuoto, vale solo per il giorno “Da”.')
                        ->nullable()
                        ->afterOrEqual('start_date'),

                    Forms\Components\TextInput::make('reason')
                        ->label('Motivo')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Da')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('A')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivo')
                    ->searchable(),
            ])
            ->defaultSort('start_date', 'desc')
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifica'),

                // Azione che conta le lezioni future nel periodo e, se ce ne sono,
                // offre un modale di conferma per rigenerarle (sposta fuori dalla chiusura).
                Tables\Actions\Action::make('rigenera')
                    ->label('Rigenera lezioni')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn (ClosureDay $record): string => 'Rigenera lezioni nel periodo ' . $record->start_date->format('d/m/Y') . ($record->end_date ? ' – ' . $record->end_date->format('d/m/Y') : ''))
                    ->modalDescription(function (ClosureDay $record): string {
                        $count = self::countAffectedLessons($record);
                        if ($count === 0) {
                            return 'Nessuna lezione futura programmata cade in questo periodo di chiusura.';
                        }
                        return "Trovate {$count} lezioni future programmate in questo periodo. Confermando, i contratti interessati verranno rigenerati e le lezioni spostate alla prima settimana utile successiva.";
                    })
                    ->modalSubmitActionLabel('Rigenera')
                    ->visible(fn (ClosureDay $record): bool => self::countAffectedLessons($record) > 0)
                    ->action(function (ClosureDay $record): void {
                        $contractIds = self::affectedContractIds($record);

                        if ($contractIds->isEmpty()) {
                            Notification::make()
                                ->title('Nessuna lezione da rigenerare')
                                ->info()
                                ->send();
                            return;
                        }

                        $generator = app(LessonGeneratorService::class);
                        $count = 0;

                        foreach ($contractIds as $id) {
                            $contract = Contract::find($id);
                            if ($contract) {
                                $generator->generateForContract($contract);
                                $count++;
                            }
                        }

                        Notification::make()
                            ->title("Rigenerazione completata: {$count} contratt" . ($count === 1 ? 'o' : 'i') . ' aggiornati.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClosureDays::route('/'),
            'create' => Pages\CreateClosureDay::route('/create'),
            'edit'   => Pages\EditClosureDay::route('/{record}/edit'),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Helper: lezioni future programmate che cadono nel periodo
    // ──────────────────────────────────────────────────────────────

    public static function countAffectedLessons(ClosureDay $record): int
    {
        return self::affectedLessonsQuery($record)->count();
    }

    public static function affectedContractIds(ClosureDay $record): \Illuminate\Support\Collection
    {
        return self::affectedLessonsQuery($record)
            ->pluck('contract_id')
            ->unique()
            ->values();
    }

    private static function affectedLessonsQuery(ClosureDay $record): \Illuminate\Database\Eloquent\Builder
    {
        $start = $record->start_date->toDateString();
        $end   = $record->end_date ? $record->end_date->toDateString() : $start;

        return \App\Models\Lesson::query()
            ->whereNull('cancelled_at')
            ->whereNull('completed_at')
            ->whereDate('starts_at', '>=', now()->startOfDay())
            ->whereDate('starts_at', '>=', $start)
            ->whereDate('starts_at', '<=', $end);
    }
}
