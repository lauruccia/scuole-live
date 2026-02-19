<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Form;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Moduli iscrizione';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('course')
                ->label('Corso')
                ->required(),

            Forms\Components\DatePicker::make('enrolled_at')
                ->label('Data iscrizione')
                ->required(),

            Forms\Components\Select::make('status')
                ->label('Stato')
                ->options([
                    'iscritto' => 'Iscritto',
                    'sospeso'  => 'Sospeso',
                    'concluso' => 'Concluso',
                    'annullato'=> 'Annullato',
                ])
                ->default('iscritto')
                ->required(),

            Forms\Components\Textarea::make('notes')
                ->label('Note')
                ->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('course')
                    ->label('Corso')
                    ->searchable(),

                Tables\Columns\TextColumn::make('enrolled_at')
                    ->label('Data iscrizione')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->badge(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Nuovo modulo iscrizione'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifica'),
                Tables\Actions\DeleteAction::make()->label('Elimina'),
            ]);
    }
}
