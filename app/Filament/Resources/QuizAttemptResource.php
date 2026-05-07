<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuizAttemptResource\Pages;
use App\Models\QuizAttempt;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizAttemptResource extends Resource
{
    protected static ?string $model = QuizAttempt::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Risultati quiz';
    protected static ?string $navigationGroup = 'Didattica';
    protected static ?int    $navigationSort  = 8;
    protected static ?string $slug            = 'quiz-results';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utente')
                    ->description(fn (QuizAttempt $r) => $r->user?->email ?? ($r->ip_address ?? '—'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('language')
                    ->label('Lingua')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('result_level')
                    ->label('Livello')
                    ->badge()
                    ->color(fn (?string $state) => match (true) {
                        in_array($state, ['A1', 'A2']) => 'gray',
                        in_array($state, ['B1', 'B2']) => 'warning',
                        in_array($state, ['C1', 'C2']) => 'success',
                        default                        => 'info',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('score_display')
                    ->label('Punteggio')
                    ->getStateUsing(fn (QuizAttempt $r) => "{$r->score} / {$r->total_questions}")
                    ->description(fn (QuizAttempt $r) => $r->score_percent . '%'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('language')
                    ->label('Lingua')
                    ->options(fn () => QuizAttempt::query()
                        ->distinct()
                        ->orderBy('language')
                        ->pluck('language', 'language')
                        ->filter()
                        ->toArray()
                    ),

                SelectFilter::make('result_level')
                    ->label('Livello')
                    ->options([
                        'A1' => 'A1', 'A2' => 'A2',
                        'B1' => 'B1', 'B2' => 'B2',
                        'C1' => 'C1', 'C2' => 'C2',
                    ]),
            ])
            ->actions([])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Elimina selezionati'),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuizAttempts::route('/'),
        ];
    }
}
