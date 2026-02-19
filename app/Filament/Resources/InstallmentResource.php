<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstallmentResource\Pages;
use App\Models\Installment;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class InstallmentResource extends Resource
{
    protected static ?string $model = Installment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Studenti';
    protected static ?string $navigationLabel = 'Scadenze e pagamenti';
    protected static ?string $pluralModelLabel = 'Scadenze e pagamenti';
    protected static ?string $modelLabel = 'Rata';
    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Rata')
                ->columns(12)
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->label('N°')
                        ->numeric()
                        ->columnSpan(2),

                    Forms\Components\Toggle::make('is_deposit')
                        ->label('Acconto')
                        ->inline(false)
                        ->columnSpan(2),

                    Forms\Components\DatePicker::make('due_date')
                        ->label('Scadenza')
                        ->native(false)
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('amount')
                        ->label('Importo')
                        ->numeric()
                        ->prefix('€')
                        ->columnSpan(3),

                    Forms\Components\Select::make('status')
                        ->label('Stato')
                        ->options([
                            'unpaid' => 'Da pagare',
                            'paid'   => 'Pagata',
                        ])
                        ->default('unpaid')
                        ->columnSpan(2),

                    Forms\Components\DateTimePicker::make('paid_at')
                        ->label('Pagata il')
                        ->seconds(false)
                        ->visible(fn (Forms\Get $get) => $get('status') === 'paid')
                        ->columnSpan(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contract.students']))

            ->columns([
                Tables\Columns\TextColumn::make('contract.students.full_name')
                    ->label('Studente')
                    ->formatStateUsing(function ($state, Installment $record) {
                        $students = $record->contract?->students ?? collect();
                        if ($students->isEmpty()) {
                            return '-';
                        }

                        // se è contratto azienda con più studenti: li mostro tutti
                        return $students->pluck('full_name')->implode(', ');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $s = trim($search);

                        return $query->whereHas('contract.students', function (Builder $q) use ($s) {
                            $q->where(function (Builder $qq) use ($s) {
                                $qq->where('first_name', 'like', "%{$s}%")
                                   ->orWhere('last_name', 'like', "%{$s}%")
                                   ->orWhereRaw("concat(first_name,' ',last_name) like ?", ["%{$s}%"]);
                            });
                        });
                    })
                    ->wrap(),

                Tables\Columns\BadgeColumn::make('number')
                    ->label('N°')
                    ->alignCenter()
                    ->color(function (Installment $record) {
                        if ($record->number === 0) return 'warning';      // tassa iscrizione (se la usi)
                        if ($record->is_deposit) return 'info';           // acconto
                        return 'gray';
                    })
                    ->formatStateUsing(function ($state, Installment $record) {
    if ($record->number === 0) return 'TASSA';
    if ($record->is_deposit)   return 'ACCONTO';

    // ✅ mostra 1..n invece di 2..n+1
    if ($record->number >= 2) return (string) ($record->number - 1);

    return (string) $record->number;
})
                    ->sortable()
                    ->width('110px'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Scadenza')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Importo')
                    ->money('EUR', locale: 'it_IT')
                    ->alignment(Alignment::End)
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Pagata il')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status_ui')
                    ->label('Stato')
                    ->getStateUsing(function (Installment $record) {
                        $isPaid = ($record->status === 'paid') || ! is_null($record->paid_at);
                        if ($isPaid) return 'Pagata';

                        $due = $record->due_date ? Carbon::parse($record->due_date)->startOfDay() : null;
                        if (! $due) return 'Da pagare';

                        if ($due->isPast()) return 'Scaduta';

                        $diff = now()->startOfDay()->diffInDays($due, false);
                        if ($diff === 0) return 'In scadenza oggi';
                        if ($diff <= 7) return 'In scadenza 7 giorni';

                        return 'Da pagare';
                    })
                    ->colors([
                        'success' => 'Pagata',
                        'danger'  => 'Scaduta',
                        'warning' => 'In scadenza oggi',
                        'info'    => 'In scadenza 7 giorni',
                        'gray'    => 'Da pagare',
                    ]),
            ])

            ->filters([
                // dropdown "Stato" come nel tuo screenshot
                SelectFilter::make('status')
                    ->label('Stato')
                    ->options([
                        'all'         => 'Tutti',
                        'due_today'   => 'In scadenza oggi',
                        'due_7'       => 'In scadenza 7 giorni',
                        'overdue'     => 'Scadute',
                        'unpaid'      => 'Da pagare',
                        'paid'        => 'Pagate',
                    ])
                    ->default('all')
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? 'all';

                        $paidQuery = fn (Builder $q) => $q->where(function (Builder $qq) {
                            $qq->where('status', 'paid')->orWhereNotNull('paid_at');
                        });

                        $unpaidQuery = fn (Builder $q) => $q->where(function (Builder $qq) {
                            $qq->whereNull('paid_at')->where('status', '!=', 'paid');
                        });

                        return match ($value) {
                            'paid' => $query->tap($paidQuery),

                            'unpaid' => $query->tap($unpaidQuery),

                            'overdue' => $query->tap($unpaidQuery)
                                ->whereDate('due_date', '<', now()->toDateString()),

                            'due_today' => $query->tap($unpaidQuery)
                                ->whereDate('due_date', now()->toDateString()),

                            'due_7' => $query->tap($unpaidQuery)
                                ->whereBetween('due_date', [now()->toDateString(), now()->copy()->addDays(7)->toDateString()]),

                            default => $query,
                        };
                    }),

                // filtro range date (Da–A)
                Filter::make('due_date_range')
                    ->label('Filtro date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Da')->native(false),
                        Forms\Components\DatePicker::make('to')->label('A')->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $from) => $q->whereDate('due_date', '>=', $from))
                            ->when($data['to'] ?? null, fn (Builder $q, $to) => $q->whereDate('due_date', '<=', $to));
                    }),
            ], layout: Tables\Enums\FiltersLayout::Dropdown)

            ->actions([
                Tables\Actions\EditAction::make()->label('Modifica'),

                Tables\Actions\Action::make('markPaid')
                    ->label('Segna pagata')
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->visible(fn (Installment $record) => !(($record->status === 'paid') || ! is_null($record->paid_at)))
                    ->requiresConfirmation()
                    ->action(function (Installment $record) {
                        $record->update([
                            'status'  => 'paid',
                            'paid_at' => $record->paid_at ?? now(),
                        ]);
                    }),

                Tables\Actions\Action::make('markUnpaid')
                    ->label('Segna non pagata')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn (Installment $record) => (($record->status === 'paid') || ! is_null($record->paid_at)))
                    ->requiresConfirmation()
                    ->action(function (Installment $record) {
                        $record->update([
                            'status'  => 'unpaid',
                            'paid_at' => null,
                        ]);
                    }),
            ])

            ->defaultSort('due_date', 'asc')
            ->searchDebounce(350)
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->paginationPageOptions([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInstallments::route('/'),
            'create' => Pages\CreateInstallment::route('/create'),
            'edit'   => Pages\EditInstallment::route('/{record}/edit'),
        ];
    }
}
