<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasAreaPermission;
use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers\ContractsRelationManager;
use App\Filament\Resources\StudentResource\RelationManagers\LessonsRelationManager;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentResource extends Resource
{
    use HasAreaPermission;

    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Studenti';
    protected static ?string $navigationLabel = 'Studenti';
    protected static ?string $modelLabel = 'Studente';
    protected static ?string $pluralModelLabel = 'Studenti';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dati principali')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('first_name')
                        ->label('Nome')->required()->maxLength(100),
                    Forms\Components\TextInput::make('last_name')
                        ->label('Cognome')->required()->maxLength(100),
                    Forms\Components\TextInput::make('fiscal_code')
                        ->label('Codice fiscale')->maxLength(16)->nullable(),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')->email()->maxLength(190)->nullable(),
                    Forms\Components\TextInput::make('phone')
                        ->label('Telefono')->tel()->maxLength(50)->nullable(),
                    Forms\Components\DatePicker::make('birth_date')
                        ->label('Data di nascita')->nullable(),
                    Forms\Components\Toggle::make('is_minor')
                        ->label('Studente minorenne')->default(false)->inline(false)->live()
                        ->afterStateUpdated(fn (Set $set, $state) => $set('parent_section_open', (bool) $state)),
                    Forms\Components\Hidden::make('parent_section_open')->dehydrated(false)->default(false),
                ]),

            Forms\Components\Section::make('Nascita')->columns(3)->schema([
                Forms\Components\TextInput::make('birth_place')->label('Città di nascita')->maxLength(255)->nullable(),
                Forms\Components\TextInput::make('birth_province')->label('Provincia')->maxLength(10)->nullable(),
                Forms\Components\TextInput::make('birth_country')->label('Nazione di nascita')->maxLength(255)->nullable(),
            ]),

            Forms\Components\Section::make('Residenza')->columns(4)->schema([
                Forms\Components\TextInput::make('residence_address')->label('Indirizzo')->maxLength(255)->columnSpanFull()->nullable(),
                Forms\Components\TextInput::make('residence_zip')->label('CAP')->maxLength(20)->nullable(),
                Forms\Components\TextInput::make('residence_city')->label('Città')->maxLength(100)->nullable(),
                Forms\Components\TextInput::make('residence_province')->label('Provincia')->maxLength(10)->nullable(),
                Forms\Components\TextInput::make('residence_country')->label('Nazione (residenza)')->maxLength(100)->nullable(),
            ]),

            Forms\Components\Section::make('Dati genitore / tutore (se minorenne)')->columns(2)->schema([
                Forms\Components\TextInput::make('parent_first_name')->label('Nome genitore')->maxLength(100)->nullable(),
                Forms\Components\TextInput::make('parent_last_name')->label('Cognome genitore')->maxLength(100)->nullable(),
                Forms\Components\TextInput::make('parent_email')->label('Email genitore')->email()->maxLength(190)->nullable(),
                Forms\Components\TextInput::make('parent_phone')->label('Telefono genitore')->tel()->maxLength(50)->nullable(),
            ])
                ->visible(fn (Get $get) => (bool) $get('is_minor'))
                ->collapsed(fn (Get $get) => ! (bool) ($get('parent_section_open') ?? false))
                ->collapsible(),

            Forms\Components\Section::make('Note')->schema([
                Forms\Components\Textarea::make('notes')->label('Note')->rows(3)->nullable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('last_name')->label('Cognome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('first_name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->toggleable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefono')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Creato il')
                    ->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifica'),
            ])
            ->filters([
                SelectFilter::make('anomaly')
                    ->label('Anomalia')
                    ->options([
                        'no_contacts' => 'Senza recapiti',
                        'minor_no_parent' => 'Minorenni senza dati genitore',
                        'duplicates' => 'Possibili duplicati',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if (! filled($value)) {
                            return $query;
                        }

                        return match ($value) {
                            'no_contacts' => $query
                                ->where(fn (Builder $q) => $q->whereNull('students.email')->orWhere('students.email', ''))
                                ->where(fn (Builder $q) => $q->whereNull('students.phone')->orWhere('students.phone', '')),

                            'minor_no_parent' => $query
                                ->where('students.is_minor', true)
                                ->where(fn (Builder $q) => $q->whereNull('students.parent_first_name')->orWhere('students.parent_first_name', '')),

                            'duplicates' => $query->whereExists(function ($sub) {
                                $sub->select(DB::raw(1))
                                    ->from('students as duplicates')
                                    ->whereRaw('LOWER(duplicates.first_name) = LOWER(students.first_name)')
                                    ->whereRaw('LOWER(duplicates.last_name) = LOWER(students.last_name)')
                                    ->whereNotNull('duplicates.first_name')
                                    ->whereNotNull('duplicates.last_name')
                                    ->groupByRaw('LOWER(duplicates.first_name), LOWER(duplicates.last_name)')
                                    ->havingRaw('COUNT(*) > 1');
                            }),

                            default => $query,
                        };
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Elimina'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ContractsRelationManager::class,
            LessonsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit'   => Pages\EditStudent::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $u = Auth::user();
        if ($u?->hasRole('Docente')) {
            $teacherId = (int) $u->id;
            $q->whereExists(function ($sub) use ($teacherId) {
                $sub->select(DB::raw(1))
                    ->from('contract_students')
                    ->whereColumn('contract_students.student_id', 'students.id')
                    ->where('contract_students.teacher_id', $teacherId);
            });
        }
        return $q;
    }
}
