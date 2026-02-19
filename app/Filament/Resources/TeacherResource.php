<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Filters\SelectFilter;

class TeacherResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Risorse Umane';
    protected static ?string $navigationLabel = 'Docenti';
    protected static ?string $modelLabel = 'Docente';
    protected static ?string $pluralModelLabel = 'Docenti';
    protected static ?int $navigationSort = 1;

    /**
     * Un docente NON deve vedere la gestione docenti.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $u = auth()->user();
        return ! $u || ! $u->hasRole('Docente');
    }

    /**
     * Mostra SOLO utenti con ruolo "Docente".
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('roles', fn (Builder $q) => $q->where('name', 'Docente'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Dati docente')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('first_name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                            $set('name', trim(($get('first_name') ?? '') . ' ' . ($get('last_name') ?? '')));
                        }),

                    Forms\Components\TextInput::make('last_name')
                        ->label('Cognome')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                            $set('name', trim(($get('first_name') ?? '') . ' ' . ($get('last_name') ?? '')));
                        }),

                    Forms\Components\TextInput::make('name')
                        ->label('Nome completo')
                        ->disabled()
                        ->dehydrated(true)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Telefono')
                        ->tel()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context) => $context === 'create')
                        ->minLength(8)
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null),

                    Forms\Components\DatePicker::make('birth_date')
                        ->label('Data di nascita')
                        ->nullable(),

                    Forms\Components\TextInput::make('birth_place')
                        ->label('Luogo di nascita')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('birth_country')
                        ->label('Nazione di nascita')
                        ->maxLength(255),
                ]),

            Section::make('Dati fiscali')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('vat_number')
                        ->label('Partita IVA')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('tax_code')
                        ->label('Codice Fiscale')
                        ->maxLength(255),
                ]),

            Section::make('Residenza')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('address')
                        ->label('Indirizzo')
                        ->columnSpanFull()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('zip')
                        ->label('CAP')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('city')
                        ->label('Città')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('province')
                        ->label('Provincia')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('country')
                        ->label('Nazione (residenza)')
                        ->maxLength(255),
                ]),

            Section::make('Dati amministrativi')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('pec')
                        ->label('Indirizzo PEC')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('iban')
                        ->label('IBAN')
                        ->maxLength(255),
                ]),

            Section::make('Contratto')
                ->columns(3)
                ->schema([
                    Select::make('teacher_contract_type')
                        ->label('Tipo di contratto')
                        ->options([
                            'dipendente' => 'Dipendente',
                            'collaborazione' => 'Collaborazione',
                            'piva' => 'Partita IVA',
                            'occasionale' => 'Prestazione occasionale',
                            'altro' => 'Altro',
                        ])
                        ->placeholder('Seleziona un’opzione')
                        ->nullable()
                        ->searchable(),

                    TextInput::make('teacher_hourly_rate_gross')
                        ->label('Tariffa oraria lorda (€)')
                        ->numeric()
                        ->minValue(0)
                        ->step('0.01')
                        ->nullable(),

                    Select::make('teacher_billing_mode')
                        ->label('Fatturazione')
                        ->options([
                            'ritenuta_20' => "Ritenuta d'acconto del 20%",
                            'senza_iva' => 'Fatturazione senza IVA',
                            'con_iva' => 'Fatturazione con IVA',
                            'nessuna' => 'Nessuna dei precedenti',
                        ])
                        ->placeholder('Seleziona un’opzione')
                        ->nullable()
                        ->searchable(),
                ]),

            Section::make('Materie')
                ->schema([
                    CheckboxList::make('teacher_subjects')
                        ->label('Materie insegnate')
                        ->options([
                            'Arabo' => 'Arabo',
                            'Francese' => 'Francese',
                            'Inglese' => 'Inglese',
                            'Spagnolo' => 'Spagnolo',
                            'Tedesco' => 'Tedesco',
                            'Italiano per stranieri' => 'Italiano per stranieri',
                        ])
                        ->columns(3)
                        ->bulkToggleable()
                        ->nullable(),
                ]),

            Section::make('Documenti')
                ->columns(2)
                ->schema([
                    Forms\Components\FileUpload::make('cv_path')
                        ->label('CV Insegnante')
                        ->directory('teachers/cv')
                        ->preserveFilenames()
                        ->maxSize(10240),

                    Forms\Components\FileUpload::make('id_doc_path')
                        ->label('Documento di identità')
                        ->directory('teachers/id')
                        ->preserveFilenames()
                        ->maxSize(10240),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Cognome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('first_name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('teacher_subjects')
                    ->label('Materie')
                    ->badge()
                    ->separator(', ')
                    ->formatStateUsing(function ($state): string {
                        if (is_array($state)) {
                            return implode(', ', $state);
                        }
                        return (string) ($state ?? '');
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('teacher_hourly_rate_gross')
                    ->label('€/h lordo')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('teacher_billing_mode')
                    ->label('Fatturazione')
                    ->formatStateUsing(function (?string $state): string {
                        return match ($state) {
                            'ritenuta_20' => "Ritenuta 20%",
                            'senza_iva' => "Senza IVA",
                            'con_iva' => "Con IVA",
                            'nessuna' => "Nessuna",
                            default => "—",
                        };
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefono')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tax_code')
                    ->label('Cod. Fiscale')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('vat_number')
                    ->label('Partita IVA')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('city')
                    ->label('Città')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('province')
                    ->label('Prov.')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creato')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_name', 'asc')
            ->filters([
                SelectFilter::make('subject')
                    ->label('Materia')
                    ->options([
                        'Arabo' => 'Arabo',
                        'Francese' => 'Francese',
                        'Inglese' => 'Inglese',
                        'Spagnolo' => 'Spagnolo',
                        'Tedesco' => 'Tedesco',
                        'Italiano per stranieri' => 'Italiano per stranieri',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;
                        if (! filled($value)) {
                            return $query;
                        }
                        return $query->whereJsonContains('teacher_subjects', $value);
                    })
                    ->searchable(),

                SelectFilter::make('teacher_billing_mode')
                    ->label('Fatturazione')
                    ->options([
                        'ritenuta_20' => "Ritenuta d'acconto 20%",
                        'senza_iva' => 'Senza IVA',
                        'con_iva' => 'Con IVA',
                        'nessuna' => 'Nessuna',
                    ])
                    ->searchable(),

                SelectFilter::make('province')
                    ->label('Provincia')
                    ->options(fn () => User::query()
                        ->whereNotNull('province')
                        ->where('province', '!=', '')
                        ->distinct()
                        ->orderBy('province')
                        ->pluck('province', 'province')
                        ->toArray()
                    )
                    ->searchable(),

                SelectFilter::make('teacher_contract_type')
                    ->label('Tipo contratto')
                    ->options([
                        'dipendente' => 'Dipendente',
                        'collaborazione' => 'Collaborazione',
                        'piva' => 'Partita IVA',
                        'occasionale' => 'Prestazione occasionale',
                        'altro' => 'Altro',
                    ])
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifica'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit'   => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }
}
