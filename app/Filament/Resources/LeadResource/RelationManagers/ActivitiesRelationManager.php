<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Models\LeadActivity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';
    protected static ?string $title       = 'Attività / Timeline';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->label('Tipo')
                ->options(LeadActivity::TYPES)
                ->required()
                ->default('note')
                ->live(),

            Forms\Components\TextInput::make('subject')
                ->label('Oggetto')
                ->maxLength(255)
                ->required(),

            Forms\Components\Textarea::make('body')
                ->label('Descrizione')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\DateTimePicker::make('occurred_at')
                ->label('Data/ora')
                ->default(now())
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('')
                    ->formatStateUsing(fn (string $state) => LeadActivity::TYPE_ICONS[$state] ?? '📌')
                    ->width(40),

                Tables\Columns\TextColumn::make('occurred_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->width(130),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state) => LeadActivity::TYPES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'call'          => 'info',
                        'email'         => 'warning',
                        'meeting'       => 'success',
                        'whatsapp'      => 'success',
                        'status_change' => 'gray',
                        default         => 'gray',
                    })
                    ->width(130),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Oggetto')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('body')
                    ->label('Descrizione')
                    ->limit(80)
                    ->wrap(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utente')
                    ->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Aggiungi attività')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
