<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherProfileResource\Pages;
use App\Models\TeacherProfile;
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
 * TeacherProfileResource — profili pubblici insegnanti (pagina /insegnanti).
 *
 * Da NON confondere con "Docenti" (TeacherResource, gestione HR degli
 * account con ruolo Docente nel gestionale): questa risorsa gestisce solo
 * il contenuto pubblicato sul sito pubblico (bio, foto, certificazioni),
 * gruppo "Sito web" come News ed Eventi e Contenuti sito — stessi ruoli
 * di accesso (superadmin/Amministrazione/Segreteria/admin).
 */
class TeacherProfileResource extends Resource
{
    protected static ?string $model = TeacherProfile::class;

    protected static ?string $navigationIcon   = 'heroicon-o-user-group';
    protected static ?string $navigationGroup  = 'Sito web';
    protected static ?string $navigationLabel  = 'Insegnanti (sito pubblico)';
    protected static ?string $modelLabel       = 'Profilo insegnante';
    protected static ?string $pluralModelLabel = 'Insegnanti (sito pubblico)';
    protected static ?int    $navigationSort   = 2;

    protected static ?string $recordTitleAttribute = 'name';

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

                Grid::make(1)->columnSpan(2)->schema([
                    Section::make('Profilo')->schema([
                        TextInput::make('name')
                            ->label('Nome visualizzato')
                            ->helperText('Es. "Insegnante di Lingua Inglese" — sul vecchio sito i profili non riportavano nomi propri, ma puoi indicare il nome reale se preferisci.')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?TeacherProfile $record) {
                                if (blank($get('slug')) && filled($state)) {
                                    $set('slug', TeacherProfile::generateSlug($state, $record?->id));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Slug (indirizzo pagina)')
                            ->required()
                            ->maxLength(255)
                            ->unique(TeacherProfile::class, 'slug', ignoreRecord: true)
                            ->helperText('Il profilo sarà visibile su /insegnanti/slug. Generato automaticamente dal nome.')
                            ->rules(['alpha_dash']),

                        TextInput::make('language')
                            ->label('Lingua / materia')
                            ->maxLength(255)
                            ->placeholder('Es. Inglese'),

                        TextInput::make('qualifications')
                            ->label('Titoli di studio')
                            ->maxLength(255)
                            ->placeholder('Es. Laurea in Lingue, Master...'),

                        Textarea::make('certifications')
                            ->label('Certificazioni / esami')
                            ->rows(2)
                            ->placeholder('Es. Trinity College London, IELTS, Cambridge...'),

                        RichEditor::make('bio')
                            ->label('Biografia')
                            ->required()
                            ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'redo', 'undo'])
                            ->columnSpanFull(),
                    ]),
                ]),

                Grid::make(1)->columnSpan(1)->schema([
                    Section::make('Pubblicazione')->schema([
                        Toggle::make('is_published')
                            ->label('Pubblicato')
                            ->helperText('Se disattivato il profilo resta in bozza, invisibile sul sito.')
                            ->onColor('success')
                            ->offColor('gray')
                            ->default(false),

                        TextInput::make('order')
                            ->label('Ordine (crescente)')
                            ->numeric()
                            ->default(0)
                            ->helperText('Determina l\'ordine di comparsa nell\'elenco /insegnanti.'),
                    ]),

                    Section::make('Foto')->schema([
                        FileUpload::make('photo')
                            ->label('')
                            ->image()
                            ->disk('public')
                            ->directory('teachers-profiles')
                            ->imageEditor()
                            ->maxSize(4096)
                            ->helperText('Facoltativa. Consigliata: quadrata, almeno 500×500 px. Max 4 MB.'),
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
                Tables\Columns\ImageColumn::make('photo')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(asset('images/logo-scuola.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('language')
                    ->label('Lingua')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Pubblicato')
                    ->boolean(),

                Tables\Columns\TextColumn::make('order')
                    ->label('Ordine')
                    ->sortable(),
            ])
            ->defaultSort('order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Pubblicato'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Vedi sul sito')
                    ->icon('heroicon-o-eye')
                    ->url(fn (TeacherProfile $record) => route('insegnanti.show', $record->slug), shouldOpenInNewTab: true)
                    ->visible(fn (TeacherProfile $record) => $record->is_published),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Nessun profilo ancora')
            ->emptyStateDescription('Crea il primo profilo insegnante: apparirà sul sito pubblico su /insegnanti.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeacherProfiles::route('/'),
            'create' => Pages\CreateTeacherProfile::route('/create'),
            'edit'   => Pages\EditTeacherProfile::route('/{record}/edit'),
        ];
    }
}
