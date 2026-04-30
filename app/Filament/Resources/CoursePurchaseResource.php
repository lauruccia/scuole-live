<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoursePurchaseResource\Pages;
use App\Models\CoursePurchase;
use App\Services\PaymentService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CoursePurchaseResource extends Resource
{
    protected static ?string $model = CoursePurchase::class;

    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Acquisti online';
    protected static ?string $modelLabel      = 'Acquisto';
    protected static ?string $pluralModelLabel = 'Acquisti online';
    protected static ?string $navigationGroup = 'Pagamenti';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Stato pagamento')->schema([
                Select::make('payment_status')
                    ->label('Stato')
                    ->options([
                        'pending'   => 'In attesa',
                        'paid'      => 'Pagato',
                        'failed'    => 'Fallito',
                        'refunded'  => 'Rimborsato',
                        'cancelled' => 'Annullato',
                    ])
                    ->required(),

                DateTimePicker::make('paid_at')
                    ->label('Data pagamento')
                    ->nullable()
                    ->native(false)
                    ->displayFormat('d/m/Y H:i'),

                Textarea::make('notes')
                    ->label('Note interne')
                    ->rows(3)
                    ->nullable(),
            ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Acquisto')->columns(3)->schema([
                TextEntry::make('id')->label('ID'),
                TextEntry::make('course.name')->label('Corso'),
                TextEntry::make('created_at')->label('Data')->dateTime('d/m/Y H:i'),
                TextEntry::make('amount')->label('Importo')->money('EUR'),
                TextEntry::make('payment_method_label')->label('Metodo'),
                TextEntry::make('status_label')->label('Stato')
                    ->badge()
                    ->color(fn (CoursePurchase $r) => match($r->payment_status) {
                        'paid'      => 'success',
                        'pending'   => 'warning',
                        'failed'    => 'danger',
                        'refunded'  => 'gray',
                        'cancelled' => 'gray',
                        default     => 'gray',
                    }),
                TextEntry::make('bank_transfer_ref')->label('Rif. Bonifico')->placeholder('—'),
                TextEntry::make('paid_at')->label('Pagato il')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('contract_id')->label('Contratto #')->placeholder('—'),
            ]),

            InfoSection::make('Acquirente')->columns(2)->schema([
                TextEntry::make('buyer_name')->label('Nome'),
                TextEntry::make('billing_email')->label('Email'),
                TextEntry::make('billing_phone')->label('Telefono')->placeholder('—'),
                TextEntry::make('billing_tax_code')->label('Codice fiscale')->placeholder('—'),
                TextEntry::make('billing_address')->label('Indirizzo')->placeholder('—'),
                TextEntry::make('billing_city')->label('Città')->placeholder('—'),
            ]),

            InfoSection::make('Note interne')->schema([
                TextEntry::make('notes')->label('Note')->placeholder('Nessuna nota'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width(60),

                Tables\Columns\TextColumn::make('buyer_name')
                    ->label('Acquirente')
                    ->searchable(query: function ($query, $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('billing_last_name', 'like', "%{$search}%")
                              ->orWhere('billing_first_name', 'like', "%{$search}%")
                              ->orWhere('billing_email', 'like', "%{$search}%")
                              ->orWhere('company_name', 'like', "%{$search}%");
                        });
                    })
                    ->description(fn (CoursePurchase $r) => $r->billing_email),

                Tables\Columns\TextColumn::make('course.name')
                    ->label('Corso')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Importo')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method_label')
                    ->label('Metodo')
                    ->badge()
                    ->color(fn (CoursePurchase $r) => match($r->payment_method) {
                        'stripe'   => 'info',
                        'paypal'   => 'primary',
                        'bonifico' => 'warning',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status_label')
                    ->label('Stato')
                    ->badge()
                    ->color(fn (CoursePurchase $r) => match($r->payment_status) {
                        'paid'      => 'success',
                        'pending'   => 'warning',
                        'failed'    => 'danger',
                        'refunded'  => 'gray',
                        'cancelled' => 'gray',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\IconColumn::make('contract_id')
                    ->label('Contratto')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('Stato')
                    ->options([
                        'pending'   => 'In attesa',
                        'paid'      => 'Pagato',
                        'failed'    => 'Fallito',
                        'refunded'  => 'Rimborsato',
                        'cancelled' => 'Annullato',
                    ]),

                SelectFilter::make('payment_method')
                    ->label('Metodo')
                    ->options([
                        'stripe'   => 'Carta di credito',
                        'paypal'   => 'PayPal',
                        'bonifico' => 'Bonifico',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('')->iconButton(),
                Tables\Actions\EditAction::make()->label('')->iconButton(),

                // Conferma manuale pagamento (es. dopo bonifico ricevuto)
                Tables\Actions\Action::make('confirm_payment')
                    ->label('Conferma pagamento')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Conferma pagamento ricevuto')
                    ->modalDescription(fn (CoursePurchase $r) =>
                        "Confermare il pagamento di €{$r->amount} per {$r->buyer_name}? Verrà creato il contratto automaticamente."
                    )
                    ->visible(fn (CoursePurchase $r) => $r->payment_status === 'pending')
                    ->action(function (CoursePurchase $r, PaymentService $svc) {
                        $svc->confirmPurchase($r, $r->payment_method, 'manual');
                        Notification::make()
                            ->title('Pagamento confermato e contratto creato.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCoursePurchases::route('/'),
            'view'   => Pages\ViewCoursePurchase::route('/{record}'),
            'edit'   => Pages\EditCoursePurchase::route('/{record}/edit'),
        ];
    }
}
