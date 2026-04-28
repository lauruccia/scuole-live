<?php

namespace App\Filament\Studente\Resources;

use App\Filament\Studente\Resources\StudentInstallmentResource\Pages;
use App\Models\Installment;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentInstallmentResource extends Resource
{
    protected static ?string $model = Installment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Scadenze e pagamenti';
    protected static ?string $navigationGroup = 'Area Studente';
    protected static ?int $navigationSort = 40;
    protected static ?string $slug = 'scadenze-pagamenti';

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

        $query = parent::getEloquentQuery()->with(['contract.students']);

        if (empty($studentIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('contract.students', function (Builder $q) use ($studentIds) {
            $q->whereIn('students.id', $studentIds);
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('N')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(function ($state, Installment $record): string {
                        if ($record->is_deposit) {
                            return 'ACCONTO';
                        }

                        return (string) ($state ?? '-');
                    })
                    ->color(fn (Installment $record): string => $record->is_deposit ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Scadenza')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Importo')
                    ->money('EUR', locale: 'it')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student_status')
                    ->label('Stato')
                    ->badge()
                    ->getStateUsing(function (Installment $record): string {
                        if ($record->paid_at) {
                            return 'Pagata';
                        }

                        if ($record->due_date && $record->due_date->isPast()) {
                            return 'Scaduta';
                        }

                        return 'Da pagare';
                    })
                    ->color(function (string $state): string {
                        return match ($state) {
                            'Pagata' => 'success',
                            'Scaduta' => 'danger',
                            default => 'warning',
                        };
                    }),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Pagata il')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_state')
                    ->label('Stato')
                    ->options([
                        'tutte' => 'Tutte',
                        'da_pagare' => 'Da pagare',
                        'pagata' => 'Pagata',
                        'scaduta' => 'Scaduta',
                    ])
                    ->default('tutte')
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? 'tutte';

                        return match ($value) {
                            'pagata' => $query->whereNotNull('paid_at'),
                            'da_pagare' => $query
                                ->whereNull('paid_at')
                                ->whereDate('due_date', '>=', now()->toDateString()),
                            'scaduta' => $query
                                ->whereNull('paid_at')
                                ->whereDate('due_date', '<', now()->toDateString()),
                            default => $query,
                        };
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()?->hasRole('Studente');
    }

    public static function canView($record): bool
    {
        $studentIds = static::getStudentIds();

        if (empty($studentIds)) {
            return false;
        }

        return $record->contract()
            ->whereHas('students', function (Builder $q) use ($studentIds) {
                $q->whereIn('students.id', $studentIds);
            })
            ->exists();
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentInstallments::route('/'),
        ];
    }
}