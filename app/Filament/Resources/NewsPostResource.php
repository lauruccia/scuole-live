<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsPostResource\Pages;
use App\Models\NewsPost;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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
use Illuminate\Support\Str;

/**
 * NewsPostResource — gestione News & Eventi del sito pubblico.
 *
 * Accessibile ad Amministrazione e Segreteria (oltre a superadmin), come
 * richiesto: entrambi devono poter pubblicare notizie "tipo WordPress".
 */
class NewsPostResource extends Resource
{
    protected static ?string $model = NewsPost::class;

    protected static ?string $navigationIcon   = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup  = 'Sito web';
    protected static ?string $navigationLabel  = 'News ed Eventi';
    protected static ?string $modelLabel       = 'News / Evento';
    protected static ?string $pluralModelLabel = 'News ed Eventi';
    protected static ?int    $navigationSort   = 1;

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

                    Section::make('Contenuto')->schema([
                        TextInput::make('title')
                            ->label('Titolo')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?NewsPost $record) {
                                // Genera lo slug solo in creazione o se ancora vuoto
                                if (blank($get('slug')) && filled($state)) {
                                    $set('slug', NewsPost::generateSlug($state, $record?->id));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Slug (indirizzo pagina)')
                            ->required()
                            ->maxLength(255)
                            ->unique(NewsPost::class, 'slug', ignoreRecord: true)
                            ->helperText('La notizia sarà visibile su /news/slug. Generato automaticamente dal titolo.')
                            ->rules(['alpha_dash']),

                        Textarea::make('excerpt')
                            ->label('Riassunto (anteprima)')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Testo breve mostrato nelle card in home e nell\'elenco news. Se vuoto viene ricavato dal contenuto.'),

                        RichEditor::make('body')
                            ->label('Testo della notizia')
                            ->required()
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'link', 'orderedList', 'bulletList', 'blockquote',
                                'h2', 'h3', 'redo', 'undo',
                            ])
                            ->columnSpanFull(),
                    ]),
                ]),

                // ── Colonna laterale (1/3) ───────────────────────────────
                Grid::make(1)->columnSpan(1)->schema([

                    Section::make('Pubblicazione')->schema([
                        Toggle::make('is_published')
                            ->label('Pubblicato')
                            ->helperText('Se disattivato la notizia resta in bozza, invisibile sul sito.')
                            ->onColor('success')
                            ->offColor('gray')
                            ->default(false),

                        DateTimePicker::make('published_at')
                            ->label('Data di pubblicazione')
                            ->seconds(false)
                            ->default(now())
                            ->helperText('Con una data futura la notizia apparirà sul sito solo da quel momento.'),

                        Select::make('type')
                            ->label('Tipo')
                            ->options(NewsPost::TYPES)
                            ->default('news')
                            ->required()
                            ->live(),

                        DatePicker::make('event_date')
                            ->label('Data evento')
                            ->visible(fn (Get $get) => $get('type') === 'evento'),

                        TextInput::make('event_location')
                            ->label('Luogo evento')
                            ->placeholder('Es. Sede A&A — Viale L. Da Vinci 193')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('type') === 'evento'),
                    ]),

                    Section::make('Immagine di copertina')->schema([
                        FileUpload::make('cover_image')
                            ->label('')
                            ->image()
                            ->disk('public')
                            ->directory('news')
                            ->imageEditor()
                            ->maxSize(4096)
                            ->helperText('Consigliata: orizzontale, almeno 1200×630 px. Max 4 MB.'),
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
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(asset('images/logo-scuola.png')),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->description(fn (NewsPost $record) => Str::limit(strip_tags($record->excerpt ?? ''), 60)),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => NewsPost::TYPES[$state] ?? $state)
                    ->color(fn (string $state) => $state === 'evento' ? 'warning' : 'info'),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Pubblicato')
                    ->boolean(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Data pubblicazione')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Autore')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(NewsPost::TYPES),
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Pubblicato'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Vedi sul sito')
                    ->icon('heroicon-o-eye')
                    ->url(fn (NewsPost $record) => route('news.show', $record->slug), shouldOpenInNewTab: true)
                    ->visible(fn (NewsPost $record) => $record->is_published),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Nessuna notizia ancora')
            ->emptyStateDescription('Crea la prima notizia o il primo evento: apparirà sul sito pubblico e in home page.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('author');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListNewsPosts::route('/'),
            'create' => Pages\CreateNewsPost::route('/create'),
            'edit'   => Pages\EditNewsPost::route('/{record}/edit'),
        ];
    }
}
