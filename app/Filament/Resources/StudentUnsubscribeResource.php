<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentUnsubscribeResource\Pages;
use App\Models\StudentUnsubscribe;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Resource Filament read-only per visualizzare e gestire le disiscrizioni email GDPR.
 *
 * - L'utente NON viene creato qui: la riga viene creata automaticamente quando
 *   un destinatario clicca il link "disiscrivi" nel footer email.
 * - L'unica azione disponibile è la cancellazione (= riabilitazione).
 */
class StudentUnsubscribeResource extends Resource
{
    protected static ?string $model = StudentUnsubscribe::class;

    protected static ?string $navigationIcon  = 'heroicon-o-no-symbol';
    protected static ?string $navigationLabel = 'Studenti disiscritti';
    protected static ?string $navigationGroup = 'Email';
    protected static ?string $modelLabel      = 'Disiscritto';
    protected static ?string $pluralModelLabel = 'Disiscritti';
    protected static ?int    $navigationSort  = 90;

    public static function canCreate(): bool
    {
        // Le righe nascono solo dal flusso pubblico /unsubscribe/{token}
        return false;
    }

    public static function canAccess(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['superadmin', 'Amministrazione', 'Segreteria']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('email')->disabled(),
            Forms\Components\TextInput::make('reason')->disabled(),
            Forms\Components\TextInput::make('ip_address')->disabled(),
            Forms\Components\DateTimePicker::make('unsubscribed_at')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('reason')->limit(50)->toggleable(),
                Tables\Columns\TextColumn::make('ip_address')->toggleable(),
                Tables\Columns\TextColumn::make('unsubscribed_at')
                    ->label('Disiscritto il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('unsubscribed_at', 'desc')
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('Riabilita')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->modalHeading('Riabilitare le comunicazioni?')
                    ->modalDescription(fn ($record) => "L'indirizzo {$record->email} tornerà a ricevere le comunicazioni promozionali. Procedere?")
                    ->modalSubmitActionLabel('Sì, riabilita'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Riabilita selezionati'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentUnsubscribes::route('/'),
        ];
    }
}
