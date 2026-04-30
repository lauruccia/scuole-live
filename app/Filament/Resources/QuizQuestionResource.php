<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuizQuestionResource\Pages;
use App\Models\QuizQuestion;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class QuizQuestionResource extends Resource
{
    protected static ?string $model = QuizQuestion::class;

    protected static ?string $navigationIcon  = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'Didattica';
    protected static ?string $navigationLabel = 'Quiz di livello';
    protected static ?string $modelLabel      = 'Domanda quiz';
    protected static ?string $pluralModelLabel = 'Domande quiz';
    protected static ?int    $navigationSort  = 7;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user?->hasAnyRole(['superadmin', 'Amministrazione', 'admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Domanda')->schema([
                Grid::make(3)->schema([
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
                        ->required()
                        ->searchable(),

                    Select::make('cefr_level')
                        ->label('Livello CEFR')
                        ->options(array_combine(QuizQuestion::CEFR_LEVELS, QuizQuestion::CEFR_LEVELS))
                        ->required()
                        ->default('A1'),

                    TextInput::make('sort_order')
                        ->label('Ordine')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                ]),

                TextInput::make('question_text')
                    ->label('Testo della domanda')
                    ->required()
                    ->maxLength(500)
                    ->placeholder('Es. What is the plural of "mouse"?')
                    ->columnSpanFull(),
            ]),

            Section::make('Opzioni di risposta')
                ->description('Inserisci esattamente 4 opzioni. Indica qual è quella corretta.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('options.0')
                            ->label('Opzione A')
                            ->required()
                            ->placeholder('Prima opzione'),

                        TextInput::make('options.1')
                            ->label('Opzione B')
                            ->required()
                            ->placeholder('Seconda opzione'),

                        TextInput::make('options.2')
                            ->label('Opzione C')
                            ->required()
                            ->placeholder('Terza opzione'),

                        TextInput::make('options.3')
                            ->label('Opzione D')
                            ->required()
                            ->placeholder('Quarta opzione'),
                    ]),

                    Select::make('correct_index')
                        ->label('Risposta corretta')
                        ->options([
                            0 => 'A — prima opzione',
                            1 => 'B — seconda opzione',
                            2 => 'C — terza opzione',
                            3 => 'D — quarta opzione',
                        ])
                        ->required()
                        ->native(false),

                    Toggle::make('is_active')
                        ->label('Domanda attiva')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('language')
                    ->label('Lingua')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('cefr_level')
                    ->label('Livello')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A1', 'A2' => 'success',
                        'B1', 'B2' => 'warning',
                        'C1', 'C2' => 'danger',
                        default    => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('question_text')
                    ->label('Domanda')
                    ->limit(70)
                    ->searchable(),

                Tables\Columns\TextColumn::make('correct_answer')
                    ->label('Risposta corretta')
                    ->getStateUsing(fn (QuizQuestion $r): string =>
                        ($r->options[$r->correct_index] ?? '—')
                    )
                    ->limit(40)
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Attiva')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ord.')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('language')
            ->filters([
                Tables\Filters\SelectFilter::make('language')
                    ->label('Lingua')
                    ->options([
                        'Arabo'                  => 'Arabo',
                        'Francese'               => 'Francese',
                        'Inglese'                => 'Inglese',
                        'Spagnolo'               => 'Spagnolo',
                        'Tedesco'                => 'Tedesco',
                        'Italiano per stranieri' => 'Italiano per stranieri',
                    ]),

                Tables\Filters\SelectFilter::make('cefr_level')
                    ->label('Livello CEFR')
                    ->options(array_combine(QuizQuestion::CEFR_LEVELS, QuizQuestion::CEFR_LEVELS)),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Solo attive'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('')->iconButton(),
                Tables\Actions\DeleteAction::make()->label('')->iconButton(),
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
            'index'  => Pages\ListQuizQuestions::route('/'),
            'create' => Pages\CreateQuizQuestion::route('/create'),
            'edit'   => Pages\EditQuizQuestion::route('/{record}/edit'),
        ];
    }
}
