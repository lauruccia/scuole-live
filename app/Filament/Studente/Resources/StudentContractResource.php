<?php

namespace App\Filament\Studente\Resources;

use App\Filament\Studente\Resources\StudentContractResource\Pages;
use App\Models\Contract;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Contratti';
    protected static ?string $navigationGroup = 'Area Studente';
    protected static ?int $navigationSort = 30;
    protected static ?string $slug = 'contratti';

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

        $query = parent::getEloquentQuery()->with(['course', 'students']);

        if (empty($studentIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('students', function (Builder $q) use ($studentIds) {
            $q->whereIn('students.id', $studentIds);
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('billing_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'company' ? 'Azienda' : 'Privato')
                    ->color(fn (?string $state) => $state === 'company' ? 'warning' : 'info'),

                Tables\Columns\TextColumn::make('billing_display_name')
                    ->label('Intestatario')
                    ->searchable(),

                Tables\Columns\TextColumn::make('course.name')
                    ->label('Corso')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('hours_purchased')
                    ->label('Ore')
                    ->sortable(),

                Tables\Columns\TextColumn::make('hours_consumed')
                    ->label('Fruite')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('stampa')
                    ->label('Apri contratto')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->url(fn (Contract $record): string => route('student.contracts.print', ['contract' => $record]))
                    ->openUrlInNewTab(),
            ])
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

        return $record->students()
            ->whereIn('students.id', $studentIds)
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
            'index' => Pages\ListStudentContracts::route('/'),
        ];
    }
}