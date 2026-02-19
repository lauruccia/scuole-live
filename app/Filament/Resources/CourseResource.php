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

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Didattica';
    protected static ?string $navigationLabel = 'Corsi';
    protected static ?string $modelLabel = 'Corso';
    protected static ?string $pluralModelLabel = 'Corsi';
    protected static ?int $navigationSort = 1;



    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dati corso')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nome corso')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('Descrizione')
                        ->rows(4)
                        ->columnSpanFull(),

                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('lessons_count')
                                ->label('Numero lezioni')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->default(0),

                            Forms\Components\TextInput::make('course_price')
                                ->label('Prezzo corso (€)')
                                ->numeric()
                                ->required()
                                ->default(0),

                            Forms\Components\TextInput::make('enrollment_fee')
                                ->label('Tassa iscrizione (€)')
                                ->numeric()
                                ->required()
                                ->default(0),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
            Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('lessons_count')->label('Lezioni')->sortable(),
            Tables\Columns\TextColumn::make('course_price')->label('Prezzo')->money('EUR', locale: 'it_IT')->sortable(),
            Tables\Columns\TextColumn::make('enrollment_fee')->label('Iscrizione')->money('EUR', locale: 'it_IT')->sortable(),
            Tables\Columns\TextColumn::make('created_at')->label('Creato')->date('d/m/Y')->sortable(),
        ])->actions([
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
