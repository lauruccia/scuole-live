<?php

namespace App\Filament\Pages;

use App\Models\SchoolSetting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ImpostazioniScuola extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Impostazioni scuola';
    protected static ?string $navigationGroup = 'Configurazione';
    protected static ?string $title           = 'Impostazioni scuola';
    protected static ?int    $navigationSort  = 99;
    protected static string  $view            = 'filament.pages.impostazioni-scuola';

    public array $data = [];

    /* ─── Accesso ────────────────────────────────────────────────────────────── */

    public static function canAccess(): bool
    {
        $u = Filament::auth()->user();
        if (! $u) return false;
        return $u->hasAnyRole(['superadmin', 'super_admin', 'Amministrazione', 'Segreteria']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /* ─── Form ───────────────────────────────────────────────────────────────── */

    public function mount(): void
    {
        $this->form->fill([
            // Brand
            'school_name'       => SchoolSetting::get('school_name', 'A&A Language Center'),
            'school_legal_name' => SchoolSetting::get('school_legal_name', ''),
            'school_address'    => SchoolSetting::get('school_address', ''),
            'school_city'       => SchoolSetting::get('school_city', ''),
            'school_zip'        => SchoolSetting::get('school_zip', ''),
            'school_phone'      => SchoolSetting::get('school_phone', ''),
            'school_mobile'     => SchoolSetting::get('school_mobile', ''),
            'school_website'    => SchoolSetting::get('school_website', ''),
            'school_email'      => SchoolSetting::get('school_email', ''),
            // Banca
            'bank_iban'         => SchoolSetting::get('bank_iban', ''),
            'bank_intestatario' => SchoolSetting::get('bank_intestatario', ''),
            // Ricevute PDF
            'ricevuta_enabled'          => SchoolSetting::bool('ricevuta_enabled', true),
            'ricevuta_label'            => SchoolSetting::get('ricevuta_label', 'RICEVUTA'),
            'ricevuta_header_note'      => SchoolSetting::get('ricevuta_header_note', ''),
            'ricevuta_thank_you_text'   => SchoolSetting::get('ricevuta_thank_you_text', ''),
            'ricevuta_disclaimer'       => SchoolSetting::get('ricevuta_disclaimer', ''),
            // Funzionalità
            'digital_signature_enabled' => SchoolSetting::bool('digital_signature_enabled', false),
            // Metodi di pagamento (toggle pubblici sul checkout)
            'payment_bonifico_enabled'  => SchoolSetting::paymentBonificoEnabled(),
            'payment_stripe_enabled'    => SchoolSetting::paymentStripeEnabled(),
            'payment_paypal_enabled'    => SchoolSetting::paymentPaypalEnabled(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([

                // ── Brand & Identità ──────────────────────────────────────────
                Section::make('Identità scuola')
                    ->description('Questi dati vengono usati in email, ricevute PDF e comunicazioni automatiche.')
                    ->icon('heroicon-o-building-office')
                    ->columns(2)
                    ->schema([
                        TextInput::make('school_name')
                            ->label('Nome commerciale')
                            ->placeholder('A&A Language Center')
                            ->required()
                            ->maxLength(150),

                        TextInput::make('school_legal_name')
                            ->label('Ragione sociale')
                            ->placeholder('A&A Language Center Srl')
                            ->maxLength(200),

                        TextInput::make('school_address')
                            ->label('Indirizzo')
                            ->placeholder('Viale Leonardo Da Vinci 193')
                            ->columnSpanFull()
                            ->maxLength(255),

                        TextInput::make('school_zip')
                            ->label('CAP')
                            ->placeholder('00145')
                            ->maxLength(10),

                        TextInput::make('school_city')
                            ->label('Città')
                            ->placeholder('Roma')
                            ->maxLength(100),

                        TextInput::make('school_phone')
                            ->label('Telefono')
                            ->placeholder('+39 06.5743734')
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('school_mobile')
                            ->label('Mobile / WhatsApp')
                            ->placeholder('+39 346 3836175')
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('school_website')
                            ->label('Sito web')
                            ->placeholder('https://www.aealanguagecenter.it')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('school_email')
                            ->label('Email pubblica')
                            ->placeholder('info@aealanguagecenter.it')
                            ->email()
                            ->maxLength(255),
                    ]),

                // ── Dati bancari ──────────────────────────────────────────────
                Section::make('Dati bancari (bonifico)')
                    ->description('Usati nelle ricevute PDF e nelle istruzioni di pagamento via bonifico.')
                    ->icon('heroicon-o-banknotes')
                    ->columns(2)
                    ->schema([
                        TextInput::make('bank_iban')
                            ->label('IBAN')
                            ->placeholder('IT60 X054 2811 1010 0000 0123 456')
                            ->maxLength(34)
                            ->rules([new \App\Rules\Iban])
                            ->helperText('Validato con checksum ISO 13616 (mod-97).'),

                        TextInput::make('bank_intestatario')
                            ->label('Intestatario conto')
                            ->placeholder('A&A Language Center Srl')
                            ->maxLength(200),
                    ]),

                // ── Ricevute PDF ──────────────────────────────────────────────
                Section::make('Ricevute di pagamento PDF')
                    ->description('Personalizza il documento scaricabile per le rate pagate.')
                    ->icon('heroicon-o-document-text')
                    ->columns(2)
                    ->schema([
                        Toggle::make('ricevuta_enabled')
                            ->label('Abilita download ricevuta per le rate pagate')
                            ->helperText('Se disattivato, il pulsante "Scarica ricevuta" non sarà visibile in nessun pannello.')
                            ->onColor('success')
                            ->offColor('gray')
                            ->columnSpanFull(),

                        TextInput::make('ricevuta_label')
                            ->label('Etichetta documento')
                            ->placeholder('RICEVUTA')
                            ->helperText('Es. "RICEVUTA", "QUIETANZA DI PAGAMENTO", "CONFERMA PAGAMENTO"')
                            ->maxLength(60),

                        TextInput::make('ricevuta_header_note')
                            ->label('Sottotitolo intestazione')
                            ->placeholder('Scuola di lingue — Roma')
                            ->helperText('Riga piccola sotto il nome scuola nell\'header del PDF.')
                            ->maxLength(150),

                        Textarea::make('ricevuta_thank_you_text')
                            ->label('Testo di ringraziamento')
                            ->placeholder('Grazie per il pagamento. Questa ricevuta conferma...')
                            ->helperText('Appare nel riquadro colorato sotto la tabella rata, solo se la rata è pagata.')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Textarea::make('ricevuta_disclaimer')
                            ->label('Nota legale / disclaimer nel footer')
                            ->placeholder('Documento generato automaticamente — Non ha valore fiscale...')
                            ->helperText('Appare in fondo al PDF in piccolo.')
                            ->rows(2)
                            ->maxLength(400)
                            ->columnSpanFull(),
                    ]),

                // ── Metodi di pagamento ───────────────────────────────────────
                Section::make('Metodi di pagamento')
                    ->description('Quali metodi di pagamento sono disponibili sul checkout pubblico (/corsi). Disabilitarne uno lo nasconde immediatamente, anche se Stripe/PayPal sono configurati nel .env.')
                    ->icon('heroicon-o-credit-card')
                    ->columns(1)
                    ->schema([
                        Toggle::make('payment_bonifico_enabled')
                            ->label('Bonifico bancario')
                            ->helperText('Lo studente riceve IBAN e causale via email; l\'attivazione del corso avviene dopo conferma manuale dell\'incasso.')
                            ->onColor('success')
                            ->offColor('gray'),

                        Toggle::make('payment_stripe_enabled')
                            ->label('Carta di credito (Stripe)')
                            ->helperText('Pagamento immediato online tramite Stripe. Richiede STRIPE_KEY, STRIPE_SECRET e STRIPE_WEBHOOK_SECRET configurati nel .env produzione.')
                            ->onColor('success')
                            ->offColor('gray'),

                        Toggle::make('payment_paypal_enabled')
                            ->label('PayPal')
                            ->helperText('Pagamento via account PayPal. Richiede PAYPAL_CLIENT_ID, PAYPAL_SECRET, PAYPAL_BASE_URL e PAYPAL_WEBHOOK_ID configurati.')
                            ->onColor('success')
                            ->offColor('gray'),
                    ]),

                // ── Firma digitale ────────────────────────────────────────────
                Section::make('Firma digitale contratti')
                    ->description('Se abilitata, gli studenti potranno firmare il proprio contratto dall\'area riservata tramite un codice OTP ricevuto via email.')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Toggle::make('digital_signature_enabled')
                            ->label('Abilita firma digitale OTP')
                            ->helperText(
                                'Quando attivo, nella pagina "Contratto" dell\'area studente apparirà il pulsante '
                                . '"Firma il contratto". Uno studente riceverà un codice via email valido 15 minuti '
                                . 'e potrà completare la firma inserendo il codice.'
                            )
                            ->onColor('success')
                            ->offColor('gray'),
                    ]),
            ]);
    }

    /* ─── Azioni ─────────────────────────────────────────────────────────────── */

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Salva impostazioni')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // Brand
        SchoolSetting::set('school_name',       $state['school_name'] ?? '');
        SchoolSetting::set('school_legal_name',  $state['school_legal_name'] ?? '');
        SchoolSetting::set('school_address',     $state['school_address'] ?? '');
        SchoolSetting::set('school_city',        $state['school_city'] ?? '');
        SchoolSetting::set('school_zip',         $state['school_zip'] ?? '');
        SchoolSetting::set('school_phone',       $state['school_phone'] ?? '');
        SchoolSetting::set('school_mobile',      $state['school_mobile'] ?? '');
        SchoolSetting::set('school_website',     $state['school_website'] ?? '');
        SchoolSetting::set('school_email',       $state['school_email'] ?? '');

        // Banca
        SchoolSetting::set('bank_iban',          $state['bank_iban'] ?? '');
        SchoolSetting::set('bank_intestatario',  $state['bank_intestatario'] ?? '');

        // Ricevute PDF
        SchoolSetting::set('ricevuta_enabled',        $state['ricevuta_enabled'] ? '1' : '0');
        SchoolSetting::set('ricevuta_label',           $state['ricevuta_label'] ?? 'RICEVUTA');
        SchoolSetting::set('ricevuta_header_note',     $state['ricevuta_header_note'] ?? '');
        SchoolSetting::set('ricevuta_thank_you_text',  $state['ricevuta_thank_you_text'] ?? '');
        SchoolSetting::set('ricevuta_disclaimer',      $state['ricevuta_disclaimer'] ?? '');

        // Funzionalità
        SchoolSetting::set('digital_signature_enabled', $state['digital_signature_enabled'] ? '1' : '0');

        // Metodi di pagamento
        SchoolSetting::set('payment_bonifico_enabled', !empty($state['payment_bonifico_enabled']) ? '1' : '0');
        SchoolSetting::set('payment_stripe_enabled',   !empty($state['payment_stripe_enabled'])   ? '1' : '0');
        SchoolSetting::set('payment_paypal_enabled',   !empty($state['payment_paypal_enabled'])   ? '1' : '0');

        Notification::make()
            ->title('Impostazioni salvate')
            ->success()
            ->send();
    }
}
