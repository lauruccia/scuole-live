<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static ?string $navigationIcon  = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Impostazioni';
    protected static ?string $navigationLabel = 'Template Email';
    protected static ?string $modelLabel      = 'Template Email';
    protected static ?string $pluralModelLabel = 'Template Email';
    protected static ?int    $navigationSort  = 10;

    // ─── Accesso ────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user?->hasAnyRole(['superadmin', 'Amministrazione', 'Segreteria', 'admin']) ?? false;
    }

    // ─── Form ────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)->schema([

                // ── Colonna principale (2/3) ─────────────────────────────
                Grid::make(1)->columnSpan(2)->schema([

                    Section::make('Intestazione')->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nome template')
                                ->required()
                                ->maxLength(190),

                            TextInput::make('slug')
                                ->label('Slug (identificatore)')
                                ->required()
                                ->maxLength(100)
                                ->unique(EmailTemplate::class, 'slug', ignoreRecord: true)
                                ->helperText('Usato nel codice per richiamare il template. Non modificare se già in uso.'),
                        ]),

                        Grid::make(3)->schema([
                            Select::make('category')
                                ->label('Categoria')
                                ->options([
                                    'Studenti'       => 'Studenti',
                                    'Lezioni'        => 'Lezioni',
                                    'Contratti'      => 'Contratti',
                                    'Comunicazioni'  => 'Comunicazioni',
                                    'Generale'       => 'Generale',
                                ])
                                ->required()
                                ->default('Generale'),

                            Select::make('trigger_event')
                                ->label('Evento automatico')
                                ->options(EmailTemplate::TRIGGER_EVENTS)
                                ->placeholder('— Solo manuale —')
                                ->nullable()
                                ->helperText('Se impostato, l\'email scatta automaticamente all\'evento selezionato.'),

                            Toggle::make('is_active')
                                ->label('Attivo')
                                ->default(true)
                                ->inline(false),
                        ]),

                        TextInput::make('subject')
                            ->label('Oggetto dell\'email')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Puoi usare variabili come {{nome}}, {{cognome}}, ecc.'),
                    ]),

                    Section::make('Corpo del messaggio')
                        ->description('Scrivi il testo dell\'email. Usa {{variabile}} per inserire valori dinamici (es. {{nome}}, {{data_lezione}}). La firma viene aggiunta automaticamente.')
                        ->schema([
                            RichEditor::make('body_html')
                                ->label('')
                                ->required()
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'underline',
                                    'strike',
                                    'link',
                                    'orderedList',
                                    'bulletList',
                                    'blockquote',
                                    'h2',
                                    'h3',
                                    'undo',
                                    'redo',
                                ])
                                ->extraAttributes(['style' => 'min-height: 340px;'])
                                ->columnSpanFull(),
                        ]),

                    Section::make('Note interne')->schema([
                        Textarea::make('notes')
                            ->label('Note (non visibili all\'utente)')
                            ->rows(3)
                            ->placeholder('Appunti per la segreteria...'),
                    ])->collapsible()->collapsed(),
                ]),

                // ── Sidebar (1/3) ────────────────────────────────────────
                Grid::make(1)->columnSpan(1)->schema([

                    Section::make('Variabili disponibili')
                        ->description('Copia la variabile e incollala nel corpo o nell\'oggetto.')
                        ->schema([
                            Placeholder::make('vars_list')
                                ->label('')
                                ->content(function (Get $get): HtmlString {
                                    $vars = $get('available_variables');

                                    if (empty($vars) || ! is_array($vars)) {
                                        return new HtmlString('<p style="color:#6b7280; font-size:13px;">Nessuna variabile definita per questo template.</p>');
                                    }

                                    $rows = '';
                                    foreach ($vars as $v) {
                                        $key  = $v['key'] ?? '';
                                        $desc = $v['description'] ?? '';
                                        $rows .= sprintf(
                                            '<tr>
                                                <td style="padding:5px 8px; font-family:monospace; font-size:13px; white-space:nowrap; color:#1e3a5f; user-select:all;">{{%s}}</td>
                                                <td style="padding:5px 8px; font-size:13px; color:#374151;">%s</td>
                                            </tr>',
                                            htmlspecialchars($key),
                                            htmlspecialchars($desc)
                                        );
                                    }

                                    return new HtmlString(
                                        '<table style="width:100%; border-collapse:collapse;">'
                                        . '<thead><tr style="background:#f3f4f6;">'
                                        . '<th style="text-align:left; padding:6px 8px; font-size:12px; color:#6b7280;">Variabile</th>'
                                        . '<th style="text-align:left; padding:6px 8px; font-size:12px; color:#6b7280;">Descrizione</th>'
                                        . '</tr></thead>'
                                        . '<tbody>' . $rows . '</tbody>'
                                        . '</table>'
                                    );
                                }),
                        ])
                        ->live(),

                    Section::make('Anteprima rapida')
                        ->description('Valori di esempio per visualizzare il template.')
                        ->schema([
                            Placeholder::make('preview_note')
                                ->label('')
                                ->content(new HtmlString(
                                    '<p style="font-size:13px; color:#6b7280; margin:0;">Salva il template e usa il pulsante <strong>Anteprima</strong> nella lista per vedere l\'email completa.</p>'
                                )),
                        ]),
                ]),
            ]),
        ]);
    }

    // ─── Table ───────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Studenti'      => 'success',
                        'Lezioni'       => 'warning',
                        'Contratti'     => 'info',
                        'Comunicazioni' => 'primary',
                        default         => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Oggetto')
                    ->limit(55)
                    ->searchable(),

                Tables\Columns\TextColumn::make('trigger_event')
                    ->label('Evento')
                    ->getStateUsing(fn (EmailTemplate $record): string =>
                        $record->trigger_event
                            ? (EmailTemplate::TRIGGER_EVENTS[$record->trigger_event] ?? $record->trigger_event)
                            : '— Manuale —'
                    )
                    ->badge()
                    ->color(fn (EmailTemplate $record): string => $record->trigger_event ? 'primary' : 'gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Attivo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modificato')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('category')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoria')
                    ->options([
                        'Studenti'       => 'Studenti',
                        'Lezioni'        => 'Lezioni',
                        'Contratti'      => 'Contratti',
                        'Comunicazioni'  => 'Comunicazioni',
                        'Generale'       => 'Generale',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Solo attivi'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Anteprima')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (EmailTemplate $record): string => 'Anteprima: ' . $record->name)
                    ->modalContent(function (EmailTemplate $record): HtmlString {
                        // Variabili di esempio per l'anteprima
                        $sampleVars = [
                            'nome'              => 'Mario',
                            'cognome'           => 'Rossi',
                            'email'             => 'mario.rossi@example.com',
                            'password'          => 'Password123!',
                            'portale_url'       => url('/'),
                            'app_name'          => config('app.name', 'A&A Language Center'),
                            'data_lezione'      => now()->format('d/m/Y'),
                            'ora_inizio'        => '10:00',
                            'ora_fine'          => '11:00',
                            'lingua'            => 'Inglese',
                            'docente'           => 'Prof.ssa Bianchi',
                            'motivo'            => 'Impegno personale del docente',
                            'numero_contratto'  => '42',
                            'nome_corso'        => 'Inglese B2 — Lezioni personalizzate',
                            'oggetto'           => 'Comunicazione importante',
                            'contenuto'         => 'Caro studente, ti scriviamo per informarti di una novità importante presso la nostra scuola.',
                        ];

                        $svc  = app(EmailTemplateService::class);
                        $html = $svc->preview($record->slug, $sampleVars);

                        if (! $html) {
                            return new HtmlString('<p class="text-gray-500">Anteprima non disponibile.</p>');
                        }

                        return new HtmlString(
                            '<div style="border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; max-height:70vh; overflow-y:auto;">'
                            . $html
                            . '</div>'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Chiudi')
                    ->slideOver(),

                Tables\Actions\Action::make('test_send')
                    ->label('Test')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->tooltip('Invia email di test al tuo indirizzo')
                    ->requiresConfirmation()
                    ->modalHeading('Invia email di test')
                    ->modalDescription(fn (): string => 'Verrà inviata un\'email di prova a: ' . (Auth::user()?->email ?? 'N/D'))
                    ->action(function (EmailTemplate $record): void {
                        $user = Auth::user();
                        if (! $user?->email) {
                            Notification::make()->title('Nessun indirizzo email')->danger()->send();
                            return;
                        }

                        $svc = app(EmailTemplateService::class);
                        $ok  = $svc->sendTemplate(
                            $record,
                            $user->email,
                            $user->name ?? 'Test',
                            [
                                'nome'             => 'Mario',
                                'cognome'          => 'Rossi',
                                'email'            => $user->email,
                                'password'         => 'Password123!',
                                'portale_url'      => url('/'),
                                'app_name'         => config('app.name'),
                                'data_lezione'     => now()->format('d/m/Y'),
                                'ora_inizio'       => '10:00',
                                'ora_fine'         => '11:00',
                                'lingua'           => 'Inglese',
                                'docente'          => 'Prof.ssa Bianchi',
                                'motivo'           => 'Test',
                                'numero_contratto' => '42',
                                'nome_corso'       => 'Inglese B2',
                                'oggetto'          => 'Email di test — ' . $record->name,
                                'contenuto'        => 'Questa è un\'email di test generata dal pannello di amministrazione.',
                            ]
                        );

                        if ($ok) {
                            Notification::make()
                                ->title('Email di test inviata!')
                                ->body('Controlla la casella di ' . $user->email)
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Errore invio')
                                ->body('Controlla i log per i dettagli.')
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->iconButton(),

                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (EmailTemplate $record): string => $record->is_active ? 'Disattiva' : 'Attiva')
                    ->icon(fn (EmailTemplate $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (EmailTemplate $record): string => $record->is_active ? 'danger' : 'success')
                    ->action(function (EmailTemplate $record): void {
                        $record->update(['is_active' => ! $record->is_active]);
                        Notification::make()
                            ->title($record->is_active ? 'Template attivato' : 'Template disattivato')
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
            'index'  => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit'   => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
