<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasAreaPermission;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
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

                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('hours_purchased')
                        ->label('Ore totali del corso')
                        ->numeric()->minValue(0)->default(0)
                        ->helperText('Lo studente sceglie la durata di ogni lezione (30min, 1h, 1.5h, 2h)'),
                    Forms\Components\TextInput::make('course_price')
                        ->label('Prezzo corso (€)')->numeric()->required()->default(0)
                        ->prefix('€'),
                    Forms\Components\TextInput::make('enrollment_fee')
                        ->label('Quota iscrizione (€)')->numeric()->required()->default(0)
                        ->prefix('€'),
                ]),
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
                    ->label('Ore')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0) . ' ore' : '—'),
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
