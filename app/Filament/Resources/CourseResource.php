<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasAreaPermission;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\CourseResource\Pages;

class CourseResource extends Resource
{
    use HasAreaPermission;

    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon  = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Didattica';
    protected static ?string $navigationLabel = 'Corsi';
    protected static ?string $modelLabel      = 'Corso';
    protected static ?string $pluralModelLabel = 'Corsi';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Dati corso')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome corso')->required()->maxLength(255)->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->label('Descrizione')->rows(3)->nullable()->columnSpanFull(),

                Forms\Components\Select::make('lesson_type')
                    ->label('Tipologia lezione')
                    ->options([
                        'Lezioni personalizzate'        => 'Lezioni personalizzate',
                        'Lezioni personalizzate + FULL' => 'Lezioni personalizzate + FULL',
                        'Lezioni di gruppo'             => 'Lezioni di gruppo',
                        'Lezioni online'                => 'Lezioni online',
                        'Corso intensivo'               => 'Corso intensivo',
                        'Esami e certificazioni'        => 'Esami e certificazioni',
                    ])
                    ->nullable()
                    ->live()
                    ->columnSpanFull(),

                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('hours_purchased')
                        ->label('Ore totali del corso')
                        ->numeric()->minValue(0)->default(0)
                        ->helperText('Lo studente sceglie la durata di ogni lezione (30min, 1h, 1.5h, 2h)'),
                    Forms\Components\TextInput::make('hours_full')
                        ->label('di cui ore FULL immersion')
                        ->numeric()->minValue(0)->default(0)
                        ->helperText('Ore on-demand (non auto-generate). Ore personalizzate = Totali − FULL.')
                        ->visible(fn (Get $get) => $get('lesson_type') === 'Lezioni personalizzate + FULL'),
                    Forms\Components\TextInput::make('course_price')
                        ->label('Prezzo corso (€)')->numeric()->required()->default(0)
                        ->prefix('€'),
                    Forms\Components\TextInput::make('enrollment_fee')
                        ->label('Quota iscrizione (€)')->numeric()->required()->default(0)
                        ->prefix('€'),
                ]),

                Forms\Components\Placeholder::make('hours_breakdown_course')
                    ->label('')
                    ->content(function (Get $get): \Illuminate\Support\HtmlString {
                        $total = (float) ($get('hours_purchased') ?? 0);
                        $full  = (float) ($get('hours_full') ?? 0);
                        if ($get('lesson_type') !== 'Lezioni personalizzate + FULL' || $total <= 0) {
                            return new \Illuminate\Support\HtmlString('');
                        }
                        $personal = max(0.0, $total - $full);
                        return new \Illuminate\Support\HtmlString(
                            '<span style="font-size:.85rem;color:#6b7280">'
                            . "🎓 Ore personalizzate: <strong>{$personal}</strong> &nbsp;|&nbsp; "
                            . "👥 Ore FULL immersion: <strong>{$full}</strong> &nbsp;|&nbsp; "
                            . "📋 Totale: <strong>{$total}</strong>"
                            . '</span>'
                        );
                    })
                    ->visible(fn (Get $get) => $get('lesson_type') === 'Lezioni personalizzate + FULL'),
            ]),

            Forms\Components\Section::make('Visibilità nel catalogo online')->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('Corso attivo (disponibile per i contratti)')
                    ->default(true)
                    ->inline(false),
                Forms\Components\Toggle::make('is_public')
                    ->label('Pubblica nel catalogo online (visibile a tutti)')
                    ->default(true)
                    ->inline(false)
                    ->helperText('Attiva per mostrare il corso nella pagina /corsi e permettere l\'iscrizione online.'),
            ])->columns(2),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable()->width(60),
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable()
                    ->description(fn (Course $r) => $r->level ?? ''),
                Tables\Columns\IconColumn::make('is_public')
                    ->label('Online')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('hours_purchased')
                    ->label('Ore totali')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0) . ' ore' : '—'),
                Tables\Columns\TextColumn::make('hours_full')
                    ->label('di cui FULL')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0) . ' ore' : '—')
                    ->default('—'),
                Tables\Columns\TextColumn::make('lesson_type')
                    ->label('Tipo')
                    ->sortable()
                    ->default('—'),
                Tables\Columns\TextColumn::make('course_price')
                    ->label('Prezzo')
                    ->money('EUR', locale: 'it_IT')->sortable(),
                Tables\Columns\TextColumn::make('enrollment_fee')
                    ->label('Iscrizione')
                    ->money('EUR', locale: 'it_IT')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Creato')->date('d/m/Y')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifica'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit'   => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
