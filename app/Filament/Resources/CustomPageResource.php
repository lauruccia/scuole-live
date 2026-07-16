<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomPageResource\Pages;
use App\Models\CustomPage;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * CustomPageResource — "page-builder" semplificato: crea pagine pubbliche
 * completamente nuove (URL a scelta, Hero + corpo libero + CTA), diverso da
 * "Contenuti sito" che modifica solo pagine già esistenti nel codice.
 *
 * Accessibile ad Amministrazione e Segreteria (oltre a superadmin/admin),
 * stessi ruoli di News ed Eventi e Insegnanti — gruppo "Sito web".
 */
class CustomPageResource extends Resource
{
    protected static ?string $model = CustomPage::class;

    protected static ?string $navigationIcon   = 'heroicon-o-document-plus';
    protected static ?string $navigationGroup  = 'Sito web';
    protected static ?string $navigationLabel  = 'Pagine personalizzate';
    protected static ?string $modelLabel       = 'Pagina personalizzata';
    protected static ?string $pluralModelLabel = 'Pagine personalizzate';
    protected static ?int    $navigationSort   = 3;

    protected static ?string $recordTitleAttribute = 'title';

    // ─── Accesso ─────────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user?->hasAnyRole(['superadmin', 'super_admin', 'Amministrazione', 'Segreteria', 'admin']) ?? false;
    }

    // ─── Form ────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)->schema([

                // ── Colonna principale (2/3) ─────────────────────────────
                Grid::make(1)->columnSpan(2)->schema([

                    Section::make('Pagina')->schema([
                        TextInput::make('title')
                            ->label('Titolo (uso interno + titolo di default)')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?CustomPage $record) {
                                if (blank($get('slug')) && filled($state)) {
                                    $set('slug', CustomPage::generateSlug($state, $record?->id));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Slug (indirizzo pagina)')
                            ->required()
                            ->maxLength(255)
                            ->unique(CustomPage::class, 'slug', ignoreRecord: true)
                            ->rules([
                                'alpha_dash',
                                function (string $attribute, $value, \Closure $fail) {
                                    if (CustomPage::isSlugReserved((string) $value)) {
                                        $fail("L'indirizzo \"/{$value}\" è già usato da un'altra pagina del sito (o è una parola riservata). Scegline un altro.");
                                    }
                                },
                            ])
                            ->helperText('La pagina sarà visibile su tuosito.it/slug. Generato automaticamente dal titolo — puoi modificarlo.'),
                    ]),

                    Section::make('Testata (Hero) — facoltativa')
                        ->description('Se lasci vuoto il titolo, viene usato il Titolo della pagina.')
                        ->collapsible()
                        ->schema([
                            TextInput::make('hero_title')
                                ->label('Titolo grande in testata')
                                ->maxLength(255),

                            Textarea::make('hero_subtitle')
                                ->label('Sottotitolo')
                                ->rows(2)
                                ->maxLength(500),

                            FileUpload::make('hero_image')
                                ->label('Immagine di testata')
                                ->image()
                                ->disk('public')
                                ->directory('custom-pages/hero')
                                ->imageEditor()
                                ->maxSize(4096)
                                ->helperText('Facoltativa. Consigliata: orizzontale, almeno 1200×630 px. Max 4 MB.'),
                        ]),

                    Section::make('Contenuto')->schema([
                        RichEditor::make('body')
                            ->label('Corpo della pagina')
                            ->required()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('custom-pages/body')
                            ->fileAttachmentsVisibility('public')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike', 'link',
                                'h2', 'h3', 'bulletList', 'orderedList', 'blockquote',
                                'attachFiles', 'redo', 'undo',
                            ])
                            ->helperText('Il blocco libero della pagina: scrivi, formatta e inserisci immagini come in un editor di testo.')
                            ->columnSpanFull(),
                    ]),

                    Section::make('Invito all\'azione (CTA) — facoltativo')
                        ->collapsible()
                        ->collapsed(fn (Get $get) => ! $get('cta_enabled'))
                        ->schema([
                            Toggle::make('cta_enabled')
                                ->label('Mostra un riquadro CTA in fondo alla pagina')
                                ->live()
                                ->onColor('success'),

                            TextInput::make('cta_title')
                                ->label('Titolo CTA')
                                ->maxLength(255)
                                ->visible(fn (Get $get) => $get('cta_enabled')),

                            TextInput::make('cta_text')
                                ->label('Testo CTA')
                                ->maxLength(500)
                                ->visible(fn (Get $get) => $get('cta_enabled')),

                            Grid::make(2)->schema([
                                TextInput::make('cta_button_label')
                                    ->label('Etichetta bottone')
                                    ->maxLength(100)
                                    ->placeholder('Es. Contattaci'),
                                TextInput::make('cta_button_url')
                                    ->label('Link bottone')
                                    ->maxLength(255)
                                    ->placeholder('Es. /contattaci oppure https://...'),
                            ])->visible(fn (Get $get) => $get('cta_enabled')),
                        ]),
                ]),

                // ── Colonna laterale (1/3) ───────────────────────────────
                Grid::make(1)->columnSpan(1)->schema([

                    Section::make('Pubblicazione')->schema([
                        Toggle::make('is_published')
                            ->label('Pubblicata')
                            ->helperText('Se disattivata la pagina resta in bozza, invisibile sul sito.')
                            ->onColor('success')
                            ->offColor('gray')
                            ->default(false),
                    ]),

                    Section::make('SEO (motori di ricerca)')
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            TextInput::make('meta_title')
                                ->label('Titolo pagina (tag title)')
                                ->maxLength(255)
                                ->helperText('Se vuoto viene usato il Titolo della pagina.'),
                            Textarea::make('meta_description')
                                ->label('Meta description')
                                ->rows(3)
                                ->maxLength(500),
                        ]),

                    Section::make('Menu di navigazione')->schema([
                        Toggle::make('show_in_menu')
                            ->label('Mostra nel menu principale')
                            ->live()
                            ->onColor('success'),
                        TextInput::make('menu_label')
                            ->label('Etichetta nel menu')
                            ->maxLength(60)
                            ->visible(fn (Get $get) => $get('show_in_menu'))
                            ->helperText('Se vuota viene usato il Titolo della pagina.'),
                        TextInput::make('menu_order')
                            ->label('Ordine (crescente)')
                            ->numeric()
                            ->default(0)
                            ->visible(fn (Get $get) => $get('show_in_menu')),
                    ]),

                    Section::make('Footer')->schema([
                        Toggle::make('show_in_footer')
                            ->label('Mostra nel footer (colonna "Esplora")')
                            ->live()
                            ->onColor('success'),
                        TextInput::make('footer_label')
                            ->label('Etichetta nel footer')
                            ->maxLength(60)
                            ->visible(fn (Get $get) => $get('show_in_footer'))
                            ->helperText('Se vuota viene usato il Titolo della pagina.'),
                    ]),
                ]),
            ]),
        ]);
    }

    // ─── Tabella ─────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Indirizzo')
                    ->formatStateUsing(fn (string $state) => '/' . $state)
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Pubblicata')
                    ->boolean(),

                Tables\Columns\IconColumn::make('show_in_menu')
                    ->label('In menu')
                    ->boolean(),

                Tables\Columns\IconColumn::make('show_in_footer')
                    ->label('In footer')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Ultima modifica')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Pubblicata'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Vedi sul sito')
                    ->icon('heroicon-o-eye')
                    ->url(fn (CustomPage $record) => route('page.show', $record->slug), shouldOpenInNewTab: true)
                    ->visible(fn (CustomPage $record) => $record->is_published),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Nessuna pagina personalizzata ancora')
            ->emptyStateDescription('Crea una nuova pagina da zero: sceglierai titolo, indirizzo e contenuto — apparirà sul sito pubblico all\'indirizzo scelto.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomPages::route('/'),
            'create' => Pages\CreateCustomPage::route('/create'),
            'edit'   => Pages\EditCustomPage::route('/{record}/edit'),
        ];
    }
}
