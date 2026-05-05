<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasAreaPermission;
use App\Filament\Resources\EnrollmentResource\Pages;
use App\Models\Enrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated Questo resource è stato sostituito da ContractResource + CoursePurchase.
 *
 * EnrollmentResource gestiva i vecchi "moduli di iscrizione" (model Enrollment).
 * La logica è ora distribuita tra:
 *   - ContractResource  → contratti didattici con studenti, orari e rate
 *   - CoursePurchase    → iscrizioni online tramite Stripe / PayPal / Bonifico
 *
 * Il resource è tenuto in codebase solo per non perdere le route già generate
 * da Filament Shield. canAccess() restituisce false in modo permanente.
 * NON rimuovere senza prima verificare che Shield non abbia permessi orfani
 * che fanno riferimento a 'page_EnrollmentResource'.
 */
class EnrollmentResource extends Resource
{
    use HasAreaPermission;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Enrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Studenti';
    protected static ?string $navigationLabel = 'Moduli iscrizione';
    protected static ?string $modelLabel = 'Modulo iscrizione';
    protected static ?string $pluralModelLabel = 'Moduli iscrizione';
    protected static ?int $navigationSort = 1;

    /** @deprecated Sempre false — resource deprecato. Vedere docblock sopra la classe. */
    public static function canAccess(): bool
    {
        return false;
    }



    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dati iscrizione')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('student_id')
                        ->label('Studente')
                        ->relationship('student', 'last_name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? '')))
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('course_name')
                        ->label('Corso')
                        ->placeholder('Es. Inglese B1 - 30 ore')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('subject_id')
                        ->label('Lingua (subject_id)')
                        ->nullable()
                        ->maxLength(50),

                    Forms\Components\DatePicker::make('enrolled_at')
                        ->label('Data iscrizione')
                        ->default(now())
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->label('Stato')
                        ->options([
                            'iscritto'  => 'Iscritto',
                            'sospeso'   => 'Sospeso',
                            'concluso'  => 'Concluso',
                            'annullato' => 'Annullato',
                        ])
                        ->default('iscritto')
                        ->required(),
                ]),

            Forms\Components\Section::make('Note')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Note')
                        ->rows(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('student.last_name')
                ->label('Studente')
                ->formatStateUsing(fn ($state, $record) => trim(($record->student?->first_name ?? '') . ' ' . ($record->student?->last_name ?? '')))
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('course_name')
                ->label('Corso')
                ->searchable(),

            Tables\Columns\TextColumn::make('enrolled_at')
                ->label('Data iscrizione')
                ->date('d/m/Y')
                ->sortable(),

            Tables\Columns\TextColumn::make('status')
                ->label('Stato')
                ->badge(),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Modifica'),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()->label('Elimina'),
            ]),
        ]);
    }

    /**
     * Docente vede SOLO iscrizioni degli studenti assegnati (contract_students.teacher_id).
     */
    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();

        $u = auth()->user();
        if ($u?->hasRole('Docente')) {
            $teacherId = (int) $u->id;

            $q->whereExists(function ($sub) use ($teacherId) {
                $sub->select(DB::raw(1))
                    ->from('contract_students')
                    ->whereColumn('contract_students.student_id', 'enrollments.student_id')
                    ->where('contract_students.teacher_id', $teacherId);
            });
        }

        return $q;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEnrollments::route('/'),
            'create' => Pages\CreateEnrollment::route('/create'),
            'edit'   => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }
}
