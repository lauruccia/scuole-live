<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationEmailLogResource\Pages;
use App\Models\NotificationEmailLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Resource Filament read-only per dare visibilità alla segreteria sugli invii email.
 *
 * Mostra l'elenco delle email inviate dal sistema (tipo, destinatario, timestamp).
 * Utile per:
 *  - capire perché uno studente non ha ricevuto una mail
 *  - verificare che i promemoria rate siano partiti
 *  - debug rapido pre-go-live
 */
class NotificationEmailLogResource extends Resource
{
    protected static ?string $model = NotificationEmailLog::class;

    protected static ?string $navigationIcon  = 'heroicon-o-envelope-open';
    protected static ?string $navigationLabel = 'Log invii email';
    protected static ?string $navigationGroup = 'Email';
    protected static ?string $modelLabel      = 'Invio email';
    protected static ?string $pluralModelLabel = 'Log invii email';
    protected static ?int    $navigationSort  = 80;

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
        return self::isSuperadmin();
    }

    public static function canAccess(): bool
    {
        return self::isSuperadmin();
    }

    public static function canViewAny(): bool
    {
        return self::isSuperadmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::isSuperadmin();
    }

    /**
     * Solo Superadmin: i log invii email possono contenere indirizzi email,
     * timestamp e metadati che non vogliamo esporre a Segreteria/Amministrazione.
     */
    private static function isSuperadmin(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['Superadmin', 'superadmin', 'super_admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('type')->disabled(),
            Forms\Components\TextInput::make('email')->disabled(),
            Forms\Components\DateTimePicker::make('sent_at')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Inviata il')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Destinatario')
                    ->searchable(),
                Tables\Columns\TextColumn::make('student_id')
                    ->label('Studente ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('installment_id')
                    ->label('Rata ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('contract_id')
                    ->label('Contratto ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reference_date')
                    ->label('Data riferimento')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sent_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(fn () => NotificationEmailLog::query()
                        ->select('type')
                        ->distinct()
                        ->pluck('type', 'type')
                        ->toArray()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationEmailLogs::route('/'),
        ];
    }
}
