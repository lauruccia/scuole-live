<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClosureDayResource\Pages;
use App\Models\ClosureDay;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
}
