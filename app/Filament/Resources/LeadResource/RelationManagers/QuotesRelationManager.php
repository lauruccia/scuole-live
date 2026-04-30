<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Models\LeadQuote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuotesRelationManager extends RelationManager
{
    protected static string $relationship = 'quotes';
    protected static ?string $title       = 'Preventivi / Offerte';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->label('Titolo offerta')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('description')
                ->label('Descrizione')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('amount')
                ->label('Importo (€)')
                ->numeric()
                ->prefix('€')
                ->required()
                ->default(0),

            Forms\Components\DatePicker::make('valid_until')
                ->label('Valida fino al')
                ->nullable(),

            Forms\Components\Select::make('status')
                ->label('Stato')
                ->options(LeadQuote::STATUSES)
                ->required()
                ->default('draft')
                ->live(),

            Forms\Components\Textarea::make('notes')
                ->label('Note interne')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->weight('semibold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Importo')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stato')
                    ->formatStateUsing(fn (string $state) => LeadQuote::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft'    => 'gray',
                        'sent'     => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Scadenza')
                    ->date('d/m/Y')
                    ->color(fn ($record) => $record?->isExpired() && $record->status === 'sent' ? 'danger' : null),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creata')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nuovo preventivo')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                // Azione rapida: marca come inviata
                Tables\Actions\Action::make('mark_sent')
                    ->label('Segna inviata')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(fn ($record) => $record->update([
                        'status'  => 'sent',
                        'sent_at' => now(),
                    ])),

                // Azione rapida: accettata
                Tables\Actions\Action::make('mark_accepted')
                    ->label('Accettata')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'sent')
                    ->action(fn ($record) => $record->update([
                        'status'       => 'accepted',
                        'responded_at' => now(),
                    ])),

                // Azione rapida: rifiutata
                Tables\Actions\Action::make('mark_rejected')
                    ->label('Rifiutata')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'sent')
                    ->action(fn ($record) => $record->update([
                        'status'       => 'rejected',
                        'responded_at' => now(),
                    ])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
