<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseMaterialResource\Pages;
use App\Models\Contract;
use App\Models\CourseMaterial;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CourseMaterialResource extends Resource
{
    protected static ?string $model = CourseMaterial::class;

    protected static ?string $navigationIcon  = 'heroicon-o-document-arrow-down';
    protected static ?string $navigationGroup = 'Didattica';
    protected static ?string $navigationLabel = 'Materiali didattici';
    protected static ?string $modelLabel      = 'Materiale';
    protected static ?string $pluralModelLabel = 'Biblioteca materiali';
    protected static ?int    $navigationSort  = 3;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole([
            'superadmin', 'Amministrazione', 'Segreteria', 'admin',
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informazioni materiale')->schema([
                Grid::make(2)->schema([
                    TextInput::make('title')
                        ->label('Titolo')
                        ->required()
                        ->maxLength(190),

                    Select::make('material_type')
                        ->label('Tipo')
                        ->options(CourseMaterial::MATERIAL_TYPES)
                        ->default('handout')
                        ->required(),
                ]),

                Grid::make(2)->schema([
                    Select::make('language')
                        ->label('Lingua')
                        ->options([
                            'Arabo'                  => 'Arabo',
                            'Francese'               => 'Francese',
                            'Inglese'                => 'Inglese',
                            'Spagnolo'               => 'Spagnolo',
                            'Tedesco'                => 'Tedesco',
                            'Italiano per stranieri' => 'Italiano per stranieri',
                        ])
                        ->nullable()
                        ->searchable(),

                    \Filament\Forms\Components\Hidden::make('uploaded_by')
                        ->default(fn () => Auth::id())
                        ->dehydrated(true),
                ]),

                Textarea::make('description')
                    ->label('Descrizione')
                    ->rows(2)
                    ->nullable(),
            ]),

            Section::make('Contenuto')->schema([
                \Filament\Forms\Components\Radio::make('content_type')
                    ->label('Tipo di contenuto')
                    ->options([
                        'file' => '📎  File (PDF, Word, immagine…)',
                        'link' => '🔗  Link esterno (YouTube, Vimeo, sito web…)',
                    ])
                    ->default('file')
                    ->inline()
                    ->live()
                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                        if ($state === 'link') {
                            $set('file_path', null);
                            $set('file_name', null);
                        } else {
                            $set('external_url', null);
                        }
                    }),

                FileUpload::make('file_path')
                    ->label('File da caricare')
                    ->disk('public')
                    ->directory('course-materials')
                    ->preserveFilenames()
                    ->maxSize(51200)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                        'text/plain', 'application/zip',
                    ])
                    ->helperText('Max 50 MB.')
                    ->visible(fn (\Filament\Forms\Get $get) => $get('content_type') !== 'link')
                    ->required(fn (\Filament\Forms\Get $get) => $get('content_type') !== 'link'),

                TextInput::make('external_url')
                    ->label('URL esterno')
                    ->placeholder('https://www.youtube.com/watch?v=...')
                    ->url()
                    ->helperText('Incolla il link di YouTube, Vimeo o qualsiasi altra risorsa web.')
                    ->visible(fn (\Filament\Forms\Get $get) => $get('content_type') === 'link')
                    ->required(fn (\Filament\Forms\Get $get) => $get('content_type') === 'link'),

                \Filament\Forms\Components\Hidden::make('file_name'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type_icon')
                    ->label('')
                    ->getStateUsing(fn (CourseMaterial $r): string => $r->type_icon)
                    ->width(40),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titolo')
                    ->searchable()
                    ->sortable()
                    ->description(fn (CourseMaterial $r): string => $r->description ?? ''),

                Tables\Columns\TextColumn::make('language')
                    ->label('Lingua')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('material_type')
                    ->label('Tipo')
                    ->getStateUsing(fn (CourseMaterial $r): string => CourseMaterial::MATERIAL_TYPES[$r->material_type] ?? $r->material_type)
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('contracts_count')
                    ->label('Assegnazioni')
                    ->counts('contracts')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('file_size_human')
                    ->label('Dimensione')
                    ->getStateUsing(fn (CourseMaterial $r): string => $r->file_size_human),

                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label('Caricato da')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                // ── Assegna a uno o più studenti ─────────────────────────────
                Tables\Actions\Action::make('assign')
                    ->label('Assegna')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->form([
                        Select::make('contracts')
                            ->label('Studenti / Contratti')
                            ->multiple()
                            ->searchable()
                            ->preload(false)
                            ->getSearchResultsUsing(fn (string $search): array =>
                                Contract::query()
                                    ->where(fn ($q) => $q
                                        ->where('billing_last_name', 'like', "%{$search}%")
                                        ->orWhere('billing_first_name', 'like', "%{$search}%")
                                        ->orWhere('company_name',      'like', "%{$search}%")
                                        ->orWhereHas('students', fn ($sq) => $sq
                                            ->where('last_name',  'like', "%{$search}%")
                                            ->orWhere('first_name', 'like', "%{$search}%")
                                        )
                                    )
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn ($c) => [
                                        $c->id => self::contractLabel($c),
                                    ])->toArray()
                            )
                            ->getOptionLabelsUsing(fn (array $values): array =>
                                Contract::whereIn('id', $values)->get()
                                    ->mapWithKeys(fn ($c) => [
                                        $c->id => self::contractLabel($c),
                                    ])->toArray()
                            )
                            ->required(),

                        Toggle::make('is_visible')
                            ->label('Visibile subito allo studente')
                            ->default(true),
                    ])
                    ->action(function (CourseMaterial $record, array $data): void {
                        $syncData = [];
                        foreach ($data['contracts'] as $contractId) {
                            $syncData[(int) $contractId] = [
                                'is_visible'  => $data['is_visible'] ? 1 : 0,
                                'assigned_at' => now(),
                            ];
                        }
                        $record->contracts()->syncWithoutDetaching($syncData);

                        Notification::make()
                            ->title('Materiale assegnato a ' . count($data['contracts']) . ' contratto/i')
                            ->success()
                            ->send();
                    }),

                // ── Mostra chi ha questo materiale assegnato ──────────────────
                Tables\Actions\Action::make('view_assignments')
                    ->label('Assegnazioni')
                    ->icon('heroicon-o-users')
                    ->color('gray')
                    ->modalHeading(fn (CourseMaterial $r) => 'Assegnazioni: ' . $r->title)
                    ->modalContent(fn (CourseMaterial $record) => view(
                        'filament.admin.course-material-assignments',
                        ['material' => $record->load('contracts')]
                    ))
                    ->modalWidth('xl'),

                Tables\Actions\Action::make('download')
                    ->label('')
                    ->tooltip(fn (CourseMaterial $r) => $r->is_link ? 'Apri link' : 'Scarica')
                    ->icon(fn (CourseMaterial $r) => $r->is_link ? 'heroicon-o-arrow-top-right-on-square' : 'heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (CourseMaterial $r): string => $r->download_url)
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make()->label('')->iconButton(),

                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->iconButton()
                    ->after(fn (CourseMaterial $r) => Storage::disk('public')->delete($r->file_path)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCourseMaterials::route('/'),
            'create' => Pages\CreateCourseMaterial::route('/create'),
            'edit'   => Pages\EditCourseMaterial::route('/{record}/edit'),
        ];
    }

    // ── Label leggibile per un contratto ─────────────────────────────────────
    private static function contractLabel(?Contract $c): string
    {
        if (! $c) return '—';
        $name = ($c->billing_type ?? 'private') === 'company'
            ? ($c->company_name ?? '—')
            : trim(($c->billing_last_name ?? '') . ' ' . ($c->billing_first_name ?? ''));
        $langs = is_array($c->languages) && count($c->languages)
            ? ' · ' . implode(', ', $c->languages)
            : '';
        return '#' . $c->id . ' — ' . $name . $langs;
    }
}
