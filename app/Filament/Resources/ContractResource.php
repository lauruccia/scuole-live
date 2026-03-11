<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasAreaPermission;
use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers;
use App\Models\BillingProfile;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ContractResource extends Resource
{
    use HasAreaPermission;

    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Studenti';
    protected static ?string $navigationLabel = 'Contratti';
    protected static ?string $modelLabel = 'Contratto';
    protected static ?string $pluralModelLabel = 'Contratti';
    protected static ?int $navigationSort = 0;

    public static function subjectOptions(): array
    {
        return [
            'Arabo' => 'Arabo',
            'Francese' => 'Francese',
            'Inglese' => 'Inglese',
            'Spagnolo' => 'Spagnolo',
            'Tedesco' => 'Tedesco',
            'Italiano per stranieri' => 'Italiano per stranieri',
        ];
    }

    protected static function lessonTypeOptions(): array
    {
        return [
            'Lezioni personalizzate' => 'Lezioni personalizzate',
            'Lezioni personalizzate + FULL' => 'Lezioni personalizzate + FULL',
            'Full immersion (piccoli gruppi)' => 'Full immersion (piccoli gruppi)',
            'Test/Examination' => 'Test/Examination',
        ];
    }

    protected static function teacherOptions(): array
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['docente', 'Docente']))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make([
                Step::make('Intestazione fattura')
                    ->schema([
                        ToggleButtons::make('billing_type')
                            ->label('Tipo intestazione')
                            ->options([
                                'private' => 'Privato',
                                'company' => 'Azienda',
                            ])
                            ->inline()
                            ->required()
                            ->default('private')
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if ($state === 'company') {
                                    $set('billing_is_student', 0);
                                } else {
                                    static::syncSingleBeneficiaryFromBilling($get, $set);
                                }
                            }),

                        Select::make('billing_profile_id')
                            ->label('Intestatario (anagrafica)')
                            ->searchable()
                            ->preload()
                            ->options(fn () => BillingProfile::query()
                                ->where('type', 'private')
                                ->orderBy('last_name')
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(fn ($p) => [
                                    $p->id => trim(($p->last_name ?? '') . ' ' . ($p->first_name ?? '')) . ($p->email ? " — {$p->email}" : ''),
                                ])->toArray()
                            )
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                if (! $state) {
                                    return;
                                }

                                $p = BillingProfile::find((int) $state);
                                if (! $p) {
                                    return;
                                }

                                $set('billing_first_name', $p->first_name);
                                $set('billing_last_name',  $p->last_name);
                                $set('billing_tax_code',   $p->fiscal_code);
                                $set('billing_vat_number', $p->vat_number);
                                $set('billing_sdi',        $p->sdi_code);
                                $set('billing_pec',        $p->pec);
                                $set('billing_email',      $p->email ? Str::lower(trim($p->email)) : null);
                                $set('billing_phone',      $p->phone);
                                $set('billing_address',    $p->address);
                                $set('billing_zip',        $p->zip);
                                $set('billing_city',       $p->city);
                                $set('billing_province',   $p->province);
                                $set('billing_country',    $p->country);

                                static::syncSingleBeneficiaryFromBilling($get, $set);
                            })
                            ->visible(fn (Get $get) => $get('billing_type') === 'private'),

                        Select::make('billing_profile_from_student')
                            ->label('Crea/aggancia intestatario da studente')
                            ->helperText('Seleziona lo studente: verrà creato (o riutilizzato) un intestatario e collegato al contratto.')
                            ->searchable()
                            ->preload(false)
                            ->dehydrated(false)
                            ->getSearchResultsUsing(function (string $search): array {
                                return Student::query()
                                    ->where(function ($q) use ($search) {
                                        $q->where('last_name', 'like', "%{$search}%")
                                            ->orWhere('first_name', 'like', "%{$search}%")
                                            ->orWhere('email', 'like', "%{$search}%");
                                    })
                                    ->orderBy('last_name')
                                    ->orderBy('first_name')
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn (Student $s) => [
                                        $s->id => trim($s->last_name . ' ' . $s->first_name) . ($s->email ? " — {$s->email}" : ''),
                                    ])->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                if (! $state) {
                                    return;
                                }

                                $s = Student::find((int) $state);
                                if (! $s) {
                                    return;
                                }

                                $p = BillingProfile::query()
                                    ->where('type', 'private')
                                    ->when($s->email, fn ($q) => $q->whereRaw('LOWER(email)=?', [Str::lower($s->email)]))
                                    ->first();

                                if (! $p) {
                                    $p = BillingProfile::create([
                                        'type'        => 'private',
                                        'first_name'  => $s->first_name,
                                        'last_name'   => $s->last_name,
                                        'email'       => $s->email ? Str::lower(trim($s->email)) : null,
                                        'phone'       => $s->phone,
                                        'city'        => $s->residence_city ?? null,
                                        'province'    => $s->residence_province ?? null,
                                        'zip'         => $s->residence_zip ?? null,
                                        'country'     => $s->residence_country ?? 'Italia',
                                        'address'     => $s->residence_address ?? null,
                                        'fiscal_code' => $s->fiscal_code ?? null,
                                    ]);
                                }

                                $set('billing_profile_id', $p->id);

                                $set('billing_first_name', $p->first_name);
                                $set('billing_last_name',  $p->last_name);
                                $set('billing_tax_code',   $p->fiscal_code);
                                $set('billing_email',      $p->email);
                                $set('billing_phone',      $p->phone);
                                $set('billing_address',    $p->address);
                                $set('billing_city',       $p->city);
                                $set('billing_province',   $p->province);
                                $set('billing_zip',        $p->zip);
                                $set('billing_country',    $p->country);

                                static::syncSingleBeneficiaryFromBilling($get, $set);
                            })
                            ->visible(fn (Get $get) => $get('billing_type') === 'private'),

                        Section::make('Dati privato')
                            ->columns(12)
                            ->visible(fn (Get $get) => $get('billing_type') === 'private')
                            ->schema([
                                TextInput::make('billing_first_name')
                                    ->label('Nome')
                                    ->required()
                                    ->maxLength(100)
                                    ->columnSpan(6)
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncSingleBeneficiaryFromBilling($get, $set)),

                                TextInput::make('billing_last_name')
                                    ->label('Cognome')
                                    ->required()
                                    ->maxLength(100)
                                    ->columnSpan(6)
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncSingleBeneficiaryFromBilling($get, $set)),

                                DatePicker::make('billing_birth_date')
                                    ->label('Nato/a il')
                                    ->nullable()
                                    ->columnSpan(4)
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncSingleBeneficiaryFromBilling($get, $set)),

                                TextInput::make('billing_birth_place')
                                    ->label('Nato/a a')
                                    ->maxLength(120)
                                    ->nullable()
                                    ->columnSpan(4)
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncSingleBeneficiaryFromBilling($get, $set)),

                                TextInput::make('billing_tax_code')
                                    ->label('Codice fiscale')
                                    ->maxLength(50)
                                    ->nullable()
                                    ->columnSpan(4),

                                TextInput::make('billing_email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(190)
                                    ->nullable()
                                    ->columnSpan(4)
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncSingleBeneficiaryFromBilling($get, $set)),

                                TextInput::make('billing_phone')
                                    ->label('Telefono')
                                    ->tel()
                                    ->maxLength(50)
                                    ->nullable()
                                    ->columnSpan(4)
                                    ->live(debounce: 300)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::syncSingleBeneficiaryFromBilling($get, $set)),

                                TextInput::make('billing_country')
                                    ->label('Nazione')
                                    ->maxLength(100)
                                    ->nullable()
                                    ->columnSpan(4),

                                TextInput::make('billing_zip')
                                    ->label('CAP')
                                    ->maxLength(20)
                                    ->nullable()
                                    ->columnSpan(4),

                                TextInput::make('billing_city')
                                    ->label('Città')
                                    ->maxLength(100)
                                    ->nullable()
                                    ->columnSpan(4),

                                TextInput::make('billing_province')
                                    ->label('Provincia')
                                    ->maxLength(50)
                                    ->nullable()
                                    ->columnSpan(4),

                                TextInput::make('billing_address')
                                    ->label('Indirizzo')
                                    ->maxLength(190)
                                    ->columnSpan(12)
                                    ->nullable(),

                                TextInput::make('billing_vat_number')
                                    ->label('Partita IVA')
                                    ->maxLength(50)
                                    ->nullable()
                                    ->columnSpan(4),

                                TextInput::make('billing_sdi')
                                    ->label('Codice SDI')
                                    ->maxLength(20)
                                    ->nullable()
                                    ->columnSpan(4),

                                TextInput::make('billing_pec')
                                    ->label('PEC')
                                    ->email()
                                    ->maxLength(190)
                                    ->nullable()
                                    ->columnSpan(4),
                            ]),

                        Select::make('company_id')
                            ->label('Azienda (se già presente)')
                            ->searchable()
                            ->preload()
                            ->options(fn () => Company::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                if (! $state) {
                                    return;
                                }

                                $c = Company::find((int) $state);
                                if (! $c) {
                                    return;
                                }

                                $set('company_name', $c->name);
                                $set('vat_number', $c->vat_number);
                                $set('company_tax_code', $c->tax_code);
                                $set('sdi', $c->sdi_code);
                                $set('pec', $c->pec);

                                $set('company_email', $c->email);
                                $set('company_phone', $c->phone);
                                $set('company_address', $c->address);
                                $set('company_city', $c->city);
                                $set('company_province', $c->province);
                                $set('company_zip', $c->zip);
                                $set('company_country', $c->country);

                                if (isset($c->billing_profile_id) && $c->billing_profile_id) {
                                    $set('billing_profile_id', $c->billing_profile_id);
                                }
                            })
                            ->visible(fn (Get $get) => $get('billing_type') === 'company'),

                        Section::make('Dati azienda')
                            ->columns(4)
                            ->visible(fn (Get $get) => $get('billing_type') === 'company')
                            ->schema([
                                TextInput::make('company_name')
                                    ->label('Ragione sociale')
                                    ->required()
                                    ->maxLength(190)
                                    ->columnSpanFull(),

                                TextInput::make('vat_number')
                                    ->label('Partita IVA')
                                    ->required()
                                    ->maxLength(50),

                                TextInput::make('sdi')
                                    ->label('Codice SDI')
                                    ->maxLength(20)
                                    ->nullable(),

                                TextInput::make('pec')
                                    ->label('PEC')
                                    ->email()
                                    ->maxLength(190)
                                    ->nullable(),

                                TextInput::make('company_email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(190)
                                    ->nullable(),

                                TextInput::make('company_phone')
                                    ->label('Telefono')
                                    ->tel()
                                    ->maxLength(50)
                                    ->nullable(),

                                TextInput::make('company_address')
                                    ->label('Indirizzo')
                                    ->maxLength(190)
                                    ->columnSpanFull()
                                    ->nullable(),

                                TextInput::make('company_city')
                                    ->label('Città')
                                    ->maxLength(100)
                                    ->nullable(),

                                TextInput::make('company_province')
                                    ->label('Provincia')
                                    ->maxLength(50)
                                    ->nullable(),

                                TextInput::make('company_zip')
                                    ->label('CAP')
                                    ->maxLength(20)
                                    ->nullable(),

                                TextInput::make('company_country')
                                    ->label('Nazione')
                                    ->maxLength(100)
                                    ->nullable(),
                            ]),
                    ]),

                Step::make('Corso acquistato')
                    ->columns(3)
                    ->schema([
                        Select::make('course_id')
                            ->label('Corso')
                            ->options(fn () => Course::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                if (! $state) {
                                    return;
                                }

                                $course = Course::find((int) $state);
                                if (! $course) {
                                    return;
                                }

                                $set('course_price', (float) ($course->course_price ?? 0));
                                $set('enrollment_fee', (float) ($course->enrollment_fee ?? 0));
                                $set('hours_purchased', (float) ($course->lessons_count ?? 0));

                                static::recalcTotals($get, $set);
                                static::recalcBeneficiariesAssignedHours($get, $set);
                            }),

                        Select::make('languages')
                            ->label('Lingue del contratto')
                            ->options(static::subjectOptions())
                            ->multiple()
                            ->required()
                            ->searchable()
                            ->helperText('La prima lingua selezionata sarà quella di default.')
                            ->live()
                            ->afterStateHydrated(function (Get $get, Set $set, $state) {
                                if (empty($state)) {
                                    $old = $get('language_id');
                                    if (filled($old)) {
                                        $set('languages', [$old]);
                                    }
                                }
                            })
                            ->afterStateUpdated(function ($state, Set $set) {
                                $first = is_array($state) ? ($state[0] ?? null) : null;
                                $set('language_id', $first);
                            }),

                        \Filament\Forms\Components\Hidden::make('language_id'),

                        Select::make('lesson_type')
                            ->label('Tipologia lezione')
                            ->options(static::lessonTypeOptions())
                            ->required(),

                        DatePicker::make('admission_date')
                            ->label('Data ammissione')
                            ->nullable()
                            ->live(),

                        DatePicker::make('starts_at')
                            ->label('Data inizio corso')
                            ->nullable(),

                        DatePicker::make('ends_at')
                            ->label('Data fine corso')
                            ->nullable()
                            ->minDate(fn (Get $get) => $get('starts_at') ?: null),

                        TextInput::make('course_price')
                            ->label('Prezzo corso')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcTotals($get, $set)),

                        TextInput::make('enrollment_fee')
                            ->label('Tassa iscrizione')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcTotals($get, $set)),

                        Placeholder::make('total_ph')
                            ->label('Totale')
                            ->content(fn (Get $get) => number_format(((float) $get('course_price') + (float) $get('enrollment_fee')), 2, ',', '.') . ' €'),

                        TextInput::make('hours_purchased')
                            ->label('Ore')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                static::recalcBeneficiariesAssignedHours($get, $set);
                            }),
                    ]),

                Step::make('Costi e pagamento')
                    ->schema([
                        Grid::make(2)->schema([
                            Section::make('Riepilogo costi')
                                ->compact()
                                ->schema([
                                    Grid::make(2)->schema([
                                        Placeholder::make('course_price_ro')
                                            ->label('Prezzo corso')
                                            ->inlineLabel()
                                            ->content(fn (Get $get) => number_format((float) $get('course_price'), 2, ',', '.') . ' €'),

                                        Placeholder::make('enrollment_fee_ro')
                                            ->label('Tassa iscrizione')
                                            ->inlineLabel()
                                            ->content(fn (Get $get) => number_format((float) $get('enrollment_fee'), 2, ',', '.') . ' €'),
                                    ]),

                                    Placeholder::make('total_ro')
                                        ->label('Totale')
                                        ->inlineLabel()
                                        ->content(fn (Get $get) => number_format(((float) $get('course_price') + (float) $get('enrollment_fee')), 2, ',', '.') . ' €'),

                                    TextInput::make('deposit')
                                        ->label('Acconto')
                                        ->inlineLabel()
                                        ->numeric()
                                        ->required()
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => static::recalcTotals($get, $set)),
                                ]),

                            Section::make('Modalità pagamento')
                                ->compact()
                                ->schema([
                                    Placeholder::make('residual_big')
                                        ->label('Residuo da rateizzare')
                                        ->content(function (Get $get) {
                                            $total = (float) $get('course_price') + (float) $get('enrollment_fee');
                                            $residual = max(0, $total - (float) $get('deposit'));

                                            return number_format($residual, 2, ',', '.') . ' €';
                                        }),

                                    ToggleButtons::make('payment_mode')
                                        ->label('Modalità pagamento')
                                        ->options([
                                            'single' => 'Unico importo',
                                            'installments' => 'Rate mensili',
                                        ])
                                        ->inline()
                                        ->required()
                                        ->default('single')
                                        ->live(),

                                    Grid::make(2)->schema([
                                        TextInput::make('installments_count')
                                            ->label('Numero rate')
                                            ->inlineLabel()
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(1)
                                            ->visible(fn (Get $get) => $get('payment_mode') === 'installments')
                                            ->required(fn (Get $get) => $get('payment_mode') === 'installments')
                                            ->live(),

                                        DatePicker::make('first_installment_date')
                                            ->label('Data 1ª rata')
                                            ->inlineLabel()
                                            ->default(now())
                                            ->visible(fn (Get $get) => $get('payment_mode') === 'installments')
                                            ->required(fn (Get $get) => $get('payment_mode') === 'installments')
                                            ->live(),
                                    ]),

                                    Placeholder::make('installments_preview')
                                        ->label('Anteprima rate')
                                        ->content(fn (Get $get) => new HtmlString(static::installmentsPreviewHtml($get)))
                                        ->visible(fn (Get $get) => $get('payment_mode') === 'installments'),
                                ]),
                        ]),
                    ]),

                Step::make('Beneficiari e dettagli')
                    ->schema([
                        ToggleButtons::make('billing_is_student')
                            ->label('Il pagante (intestatario) coincide con lo studente beneficiario?')
                            ->options([1 => 'Sì (studente)', 0 => 'No (genitore/terzo)'])
                            ->inline()
                            ->default(0)
                            ->live()
                            ->disabled(fn (Get $get) => $get('billing_type') === 'company')
                            ->afterStateHydrated(function (Get $get, Set $set) {
                                if ($get('billing_type') === 'company') {
                                    $set('billing_is_student', 0);
                                    return;
                                }

                                if ((int) ($get('billing_is_student') ?? 0) === 1) {
                                    static::syncSingleBeneficiaryFromBilling($get, $set);
                                }
                            })
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if ($get('billing_type') === 'company') {
                                    $set('billing_is_student', 0);
                                    return;
                                }

                                if ((int) $state === 1) {
                                    static::syncSingleBeneficiaryFromBilling($get, $set);
                                }
                            }),

                        Repeater::make('beneficiaries')
                            ->label('Studenti beneficiari')
                            ->relationship('beneficiaries')
                            ->addActionLabel('Aggiungi studente')
                            ->defaultItems(0)
                            ->addable(fn (Get $get) => (int) ($get('../../billing_is_student') ?? 0) !== 1)
                            ->deletable(fn (Get $get) => (int) ($get('../../billing_is_student') ?? 0) !== 1)
                            ->reorderable(fn (Get $get) => (int) ($get('../../billing_is_student') ?? 0) !== 1)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                static::recalcBeneficiariesAssignedHours($get, $set);
                            })
                            ->schema([
                                Placeholder::make('auto_match_label')
                                    ->label('')
                                    ->content(fn (Get $get) => $get('auto_match_label'))
                                    ->visible(fn (Get $get) => filled($get('auto_match_label')))
                                    ->dehydrated(false)
                                    ->columnSpanFull(),

                                Select::make('student_id')
                                    ->label('Studente esistente (opzionale)')
                                    ->searchable()
                                    ->preload(false)
                                    ->getSearchResultsUsing(function (string $search): array {
                                        return Student::query()
                                            ->where(function ($q) use ($search) {
                                                $q->where('last_name', 'like', "%{$search}%")
                                                    ->orWhere('first_name', 'like', "%{$search}%");
                                            })
                                            ->orderBy('last_name')
                                            ->orderBy('first_name')
                                            ->limit(20)
                                            ->get()
                                            ->mapWithKeys(function (Student $s) {
                                                $label = trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));
                                                return [$s->id => $label];
                                            })
                                            ->toArray();
                                    })
                                    ->getOptionLabelUsing(fn ($value): ?string => Student::find($value)?->full_name)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        if (! $state) {
                                            return;
                                        }

                                        $s = Student::find((int) $state);
                                        if (! $s) {
                                            return;
                                        }

                                        $set('beneficiary_first_name', $s->first_name);
                                        $set('beneficiary_last_name', $s->last_name);
                                        $set('beneficiary_email', $s->email);
                                        $set('beneficiary_phone', $s->phone);
                                        $set('beneficiary_birth_date', $s->birth_date);
                                        $set('beneficiary_birth_place', $s->birth_place);
                                        $set('auto_birth_province', $s->birth_province ?? null);
                                        $set('auto_match_label', 'Selezionato: ' . $s->full_name);
                                    })
                                    ->visible(fn (Get $get) => (int) ($get('../../billing_is_student') ?? 0) !== 1),

                                Section::make('Anagrafica beneficiario')
                                    ->compact()
                                    ->visible(fn (Get $get) => (int) ($get('../../billing_is_student') ?? 0) !== 1)
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('beneficiary_first_name')
                                                ->label('Nome')
                                                ->required()
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(fn (Get $get, Set $set) => static::tryAutoLinkStudentInRepeater($get, $set)),

                                            TextInput::make('beneficiary_last_name')
                                                ->label('Cognome')
                                                ->required()
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(fn (Get $get, Set $set) => static::tryAutoLinkStudentInRepeater($get, $set)),

                                            TextInput::make('beneficiary_email')
                                                ->label('Email')
                                                ->email()
                                                ->nullable()
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(fn (Get $get, Set $set) => static::tryAutoLinkStudentInRepeater($get, $set)),

                                            TextInput::make('beneficiary_phone')
                                                ->label('Telefono')
                                                ->tel()
                                                ->nullable()
                                                ->live(debounce: 500)
                                                ->afterStateUpdated(fn (Get $get, Set $set) => static::tryAutoLinkStudentInRepeater($get, $set)),
                                        ]),

                                        Grid::make(2)->schema([
                                            DatePicker::make('beneficiary_birth_date')
                                                ->label('Nato/a il')
                                                ->nullable()
                                                ->live()
                                                ->afterStateUpdated(fn (Get $get, Set $set) => static::tryAutoLinkStudentInRepeater($get, $set)),

                                            TextInput::make('beneficiary_birth_place')
                                                ->label('Nato/a a')
                                                ->nullable()
                                                ->maxLength(120),
                                        ]),

                                        TextInput::make('assigned_hours')
                                            ->label('Ore assegnate')
                                            ->numeric()
                                            ->step(1)
                                            ->minValue(1)
                                            ->nullable()
                                            ->live(onBlur: true)
                                            ->helperText('Default automatico in ore intere. Il resto viene assegnato all’ultimo studente.')
                                            ->columnSpanFull(),

                                        Placeholder::make('auto_birth_province')
                                            ->label('Provincia (da anagrafica studente)')
                                            ->content(fn (Get $get) => $get('auto_birth_province') ?: '—')
                                            ->visible(fn (Get $get) => filled($get('student_id')))
                                            ->dehydrated(false)
                                            ->columnSpanFull(),
                                    ]),

                                Placeholder::make('billing_summary')
                                    ->label('Beneficiario (da intestazione)')
                                    ->content(function (Get $get) {
                                        $first = (string) $get('../../billing_first_name');
                                        $last  = (string) $get('../../billing_last_name');
                                        $email = (string) $get('../../billing_email');
                                        $phone = (string) $get('../../billing_phone');

                                        $name = trim($first . ' ' . $last);
                                        $lines = array_filter([
                                            $name !== '' ? $name : null,
                                            $email !== '' ? $email : null,
                                            $phone !== '' ? $phone : null,
                                        ]);

                                        return implode(' — ', $lines) ?: '—';
                                    })
                                    ->visible(fn (Get $get) => (int) ($get('../../billing_is_student') ?? 0) === 1)
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Ripartizione ore')
                            ->schema([
                                Placeholder::make('assigned_hours_summary')
                                    ->label('Riepilogo')
                                    ->content(function (Get $get) {
                                        $beneficiaries = $get('beneficiaries') ?? [];
                                        $totalAssigned = collect($beneficiaries)
                                            ->sum(fn ($row) => (float) ($row['assigned_hours'] ?? 0));
                                        $hoursPurchased = (float) ($get('hours_purchased') ?? 0);
                                        $delta = $hoursPurchased - $totalAssigned;

                                        return "Ore contratto: {$hoursPurchased} — Ore assegnate: {$totalAssigned} — Residuo: {$delta}";
                                    }),
                            ]),

                        Section::make('Note')
                            ->schema([
                                Textarea::make('notes')->label('Note contratto')->rows(3),
                            ]),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    protected static function syncSingleBeneficiaryFromBilling(Get $get, Set $set): void
    {
        if (($get('billing_type') ?? 'private') === 'company') {
            $set('billing_is_student', 0);
            return;
        }

        if ((int) ($get('billing_is_student') ?? 0) !== 1) {
            return;
        }

        $hoursPurchased = (int) floor((float) ($get('hours_purchased') ?? 0));

        $set('beneficiaries', [[
            'student_id'               => null,
            'beneficiary_first_name'   => (string) $get('billing_first_name'),
            'beneficiary_last_name'    => (string) $get('billing_last_name'),
            'beneficiary_email'        => (string) $get('billing_email'),
            'beneficiary_phone'        => (string) $get('billing_phone'),
            'beneficiary_birth_date'   => $get('billing_birth_date'),
            'beneficiary_birth_place'  => (string) $get('billing_birth_place'),
            'assigned_hours'           => $hoursPurchased > 0 ? $hoursPurchased : null,
        ]]);
    }

    protected static function recalcTotals(Get $get, Set $set): void
    {
        $course  = (float) $get('course_price');
        $fee     = (float) $get('enrollment_fee');
        $deposit = (float) $get('deposit');

        if ($deposit < 0) {
            $set('deposit', 0);
            $deposit = 0;
        }

        $total = $course + $fee;

        if ($deposit > $total) {
            $set('deposit', $total);
        }
    }

    protected static function recalcBeneficiariesAssignedHours(Get $get, Set $set): void
    {
        $beneficiaries = $get('beneficiaries') ?? [];
        if (! is_array($beneficiaries) || count($beneficiaries) === 0) {
            return;
        }

        $hoursPurchased = (int) floor((float) ($get('hours_purchased') ?? 0));
        if ($hoursPurchased <= 0) {
            return;
        }

        $filledIndexes = [];
        $emptyIndexes = [];

        foreach ($beneficiaries as $index => $row) {
            $value = $row['assigned_hours'] ?? null;
            if ($value === null || $value === '') {
                $emptyIndexes[] = $index;
            } else {
                $filledIndexes[] = $index;
            }
        }

        if (count($filledIndexes) === count($beneficiaries)) {
            return;
        }

        $studentsCount = count($beneficiaries);
        $base = intdiv($hoursPurchased, max(1, $studentsCount));
        $remainder = $hoursPurchased - ($base * $studentsCount);

        foreach ($beneficiaries as $index => &$row) {
            if (in_array($index, $filledIndexes, true)) {
                continue;
            }

            $row['assigned_hours'] = $base;
        }
        unset($row);

        if (! empty($beneficiaries)) {
            $lastIndex = array_key_last($beneficiaries);
            $current = (int) ($beneficiaries[$lastIndex]['assigned_hours'] ?? 0);
            $beneficiaries[$lastIndex]['assigned_hours'] = $current + $remainder;
        }

        $set('beneficiaries', $beneficiaries);
    }

    protected static function installmentsPreviewHtml(Get $get): string
    {
        $paymentMode = (string) ($get('payment_mode') ?? 'single');

        $total = (float) $get('course_price') + (float) $get('enrollment_fee');
        $deposit = (float) $get('deposit');

        $admission = $get('admission_date')
            ? Carbon::parse($get('admission_date'))
            : now();

        $first = $get('first_installment_date')
            ? Carbon::parse($get('first_installment_date'))
            : $admission->copy()->addDays(15);

        $rows = '';

        if ($deposit > 0) {
            $rows .= '<tr>
                <td style="padding:6px 8px;">Rata 0 (Acconto)</td>
                <td style="padding:6px 8px;">' . $admission->format('d/m/Y') . '</td>
                <td style="padding:6px 8px; text-align:right; font-weight:600;">' . number_format($deposit, 2, ',', '.') . ' €</td>
            </tr>';
        }

        $residual = max(0, $total - $deposit);

        if ($paymentMode !== 'installments') {
            if ($residual > 0) {
                $rows .= '<tr>
                    <td style="padding:6px 8px;">Saldo</td>
                    <td style="padding:6px 8px;">' . $first->format('d/m/Y') . '</td>
                    <td style="padding:6px 8px; text-align:right; font-weight:600;">' . number_format($residual, 2, ',', '.') . ' €</td>
                </tr>';
            } elseif ($deposit <= 0) {
                return '<div style="color:#6b7280">Nessun importo.</div>';
            }

            return '<div style="border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead style="background:#f9fafb;">
                        <tr>
                            <th style="text-align:left; padding:8px;">Rata</th>
                            <th style="text-align:left; padding:8px;">Scadenza</th>
                            <th style="text-align:right; padding:8px;">Importo</th>
                        </tr>
                    </thead>
                    <tbody>' . $rows . '</tbody>
                </table>
            </div>';
        }

        $count = (int) $get('installments_count');
        if ($count < 1) {
            return '<div style="color:#6b7280">Inserisci il numero di rate.</div>';
        }

        if ($residual <= 0) {
            return '<div style="border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead style="background:#f9fafb;">
                        <tr>
                            <th style="text-align:left; padding:8px;">Rata</th>
                            <th style="text-align:left; padding:8px;">Scadenza</th>
                            <th style="text-align:right; padding:8px;">Importo</th>
                        </tr>
                    </thead>
                    <tbody>' . $rows . '</tbody>
                </table>
            </div>';
        }

        if ($count === 1) {
            $rows .= '<tr>
                <td style="padding:6px 8px;">Rata 1</td>
                <td style="padding:6px 8px;">' . $first->format('d/m/Y') . '</td>
                <td style="padding:6px 8px; text-align:right; font-weight:600;">' . number_format($residual, 2, ',', '.') . ' €</td>
            </tr>';

            return '<div style="border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead style="background:#f9fafb;">
                        <tr>
                            <th style="text-align:left; padding:8px;">Rata</th>
                            <th style="text-align:left; padding:8px;">Scadenza</th>
                            <th style="text-align:right; padding:8px;">Importo</th>
                        </tr>
                    </thead>
                    <tbody>' . $rows . '</tbody>
                </table>
            </div>';
        }

        $base = floor(($residual / $count) * 100) / 100;
        $sum  = $base * $count;
        $diff = round($residual - $sum, 2);

        for ($i = 1; $i <= $count; $i++) {
            $date = $first->copy()->addMonths($i - 1)->format('d/m/Y');
            $amount = $base;

            if ($i === $count && $diff != 0.0) {
                $amount = round($base + $diff, 2);
            }

            $rows .= '<tr>
                <td style="padding:6px 8px;">Rata ' . $i . '</td>
                <td style="padding:6px 8px;">' . $date . '</td>
                <td style="padding:6px 8px; text-align:right; font-weight:600;">' . number_format($amount, 2, ',', '.') . ' €</td>
            </tr>';
        }

        return '<div style="border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
            <table style="width:100%; border-collapse:collapse;">
                <thead style="background:#f9fafb;">
                    <tr>
                        <th style="text-align:left; padding:8px;">Rata</th>
                        <th style="text-align:left; padding:8px;">Scadenza</th>
                        <th style="text-align:right; padding:8px;">Importo</th>
                    </tr>
                </thead>
                <tbody>' . $rows . '</tbody>
            </table>
        </div>';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),

                Tables\Columns\TextColumn::make('billing_type')
                    ->label('Tipo')
                    ->getStateUsing(fn (Contract $record) => ($record->billing_type ?? 'private') === 'company' ? 'Azienda' : 'Privato')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Azienda' ? 'warning' : 'info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('intestatario')
                    ->label('Intestatario')
                    ->getStateUsing(function (Contract $record): string {
                        if (($record->billing_type ?? 'private') === 'company') {
                            return (string) ($record->company_name ?: '—');
                        }

                        $first = trim((string) ($record->billing_first_name ?? ''));
                        $last  = trim((string) ($record->billing_last_name ?? ''));
                        return trim($last . ' ' . $first) ?: '—';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->where('company_name', 'like', "%{$search}%")
                                ->orWhere('billing_last_name', 'like', "%{$search}%")
                                ->orWhere('billing_first_name', 'like', "%{$search}%")
                                ->orWhereRaw(
                                    "concat(coalesce(billing_last_name,''),' ',coalesce(billing_first_name,'')) like ?",
                                    ["%{$search}%"]
                                );
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderByRaw("
                                CASE
                                    WHEN contracts.billing_type = 'company' THEN COALESCE(contracts.company_name,'')
                                    ELSE CONCAT(COALESCE(contracts.billing_last_name,''), ' ', COALESCE(contracts.billing_first_name,''))
                                END {$direction}
                            ")
                            ->orderBy('contracts.id', 'desc');
                    }),

                Tables\Columns\TextColumn::make('course.name')->label('Corso')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('hours_purchased')->label('Ore'),
                Tables\Columns\TextColumn::make('hours_consumed')->label('Fruite'),
                Tables\Columns\TextColumn::make('created_at')->label('Data')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Filter::make('period')
                    ->label('Periodo')
                    ->form([
                        FormSelect::make('preset')
                            ->label('Preset')
                            ->options([
                                'custom' => 'Personalizzate',
                                'month_current' => 'Mese corrente',
                                'month_previous' => 'Mese precedente',
                                'year_current' => 'Anno corrente',
                                'year_previous' => 'Anno precedente',
                            ])
                            ->default('custom')
                            ->live(),

                        DatePicker::make('from')
                            ->label('Da')
                            ->visible(fn ($get) => ($get('preset') ?? 'custom') === 'custom')
                            ->nullable(),

                        DatePicker::make('to')
                            ->label('A')
                            ->visible(fn ($get) => ($get('preset') ?? 'custom') === 'custom')
                            ->nullable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $preset = $data['preset'] ?? 'custom';

                        if ($preset === 'custom' && empty($data['from']) && empty($data['to'])) {
                            return $query;
                        }

                        [$from, $to] = match ($preset) {
                            'month_current'  => [now()->startOfMonth(), now()->endOfMonth()],
                            'month_previous' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
                            'year_current'   => [now()->startOfYear(), now()->endOfYear()],
                            'year_previous'  => [now()->subYearNoOverflow()->startOfYear(), now()->subYearNoOverflow()->endOfYear()],
                            default => [
                                filled($data['from'] ?? null) ? Carbon::parse($data['from'])->startOfDay() : null,
                                filled($data['to'] ?? null) ? Carbon::parse($data['to'])->endOfDay() : null,
                            ],
                        };

                        return $query
                            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from))
                            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', $to));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->iconButton(),

                Tables\Actions\Action::make('print')
                    ->label('')
                    ->tooltip('Stampa')
                    ->icon('heroicon-o-printer')
                    ->iconButton()
                    ->url(fn (Contract $record) => route('contracts.print', $record))
                    ->openUrlInNewTab(),

                \Filament\Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('generate_meet')
                        ->label('Genera Meet')
                        ->icon('heroicon-o-video-camera')
                        ->color('primary')
                        ->visible(function (): bool {
                            $u = Auth::user();
                            return $u?->hasAnyRole(['superadmin', 'Amministrazione', 'Segreteria']) ?? false;
                        })
                        ->requiresConfirmation()
                        ->action(function (Contract $record) {
                            $svc = app(GoogleCalendarService::class);
                            $res = $svc->generateMeetForContract($record, false);

                            Notification::make()
                                ->title('Meet generato')
                                ->body(
                                    "Beneficiari aggiornati: {$res['updated_students']}\n" .
                                    "Lezioni future aggiornate: {$res['updated_lessons']}\n" .
                                    "Eventi upsertati: {$res['upserted_events']}"
                                )
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('download_pdf')
                        ->label('Scarica PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn (Contract $record) => route('contracts.download', $record))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('send_email')
                        ->label('Invia Email')
                        ->icon('heroicon-o-paper-airplane')
                        ->requiresConfirmation()
                        ->action(function (Contract $record) {
                            $record->loadMissing(['course', 'beneficiaries']);

                            $to = [];

                            if (($record->billing_type ?? 'private') === 'company') {
                                $to[] = $record->company_email ?: $record->pec;
                            } else {
                                $to[] = $record->billing_email;
                            }

                            foreach (($record->beneficiaries ?? []) as $b) {
                                if (! empty($b->beneficiary_email)) {
                                    $to[] = $b->beneficiary_email;
                                }
                            }

                            $to = array_values(array_unique(array_filter($to)));

                            $pdfBinary = \Barryvdh\DomPDF\Facade\Pdf::loadView('contracts.print', [
                                'contract' => $record,
                                'mode' => 'pdf',
                            ])->setPaper('a4', 'portrait')->output();

                            $cc = array_filter([config('mail.from.address')]);

                            Mail::to($to)
                                ->cc($cc)
                                ->send(new \App\Mail\ContractPdfMail($record, $pdfBinary));

                            Notification::make()
                                ->title('Email inviata')
                                ->body('Contratto inviato a: ' . implode(', ', $to))
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $u = Auth::user();

        if ($u?->hasAnyRole(['docente', 'Docente'])) {
            $teacherId = (int) $u->id;

            $q->whereExists(function ($sub) use ($teacherId) {
                $sub->select(DB::raw(1))
                    ->from('contract_students')
                    ->whereColumn('contract_students.contract_id', 'contracts.id')
                    ->where('contract_students.teacher_id', $teacherId);
            });
        }

        return $q;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'edit'   => Pages\EditContract::route('/{record}/edit'),
        ];
    }

    protected static function findStudentForBeneficiary(array $state): ?Student
    {
        $first = Str::of((string) ($state['beneficiary_first_name'] ?? ''))->lower()->squish()->toString();
        $last  = Str::of((string) ($state['beneficiary_last_name'] ?? ''))->lower()->squish()->toString();
        $email = Str::of((string) ($state['beneficiary_email'] ?? ''))->lower()->squish()->toString();
        $phone = preg_replace('/\s+/', '', (string) ($state['beneficiary_phone'] ?? ''));
        $birth = $state['beneficiary_birth_date'] ?? null;

        if ($email !== '') {
            return Student::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
        }

        if ($phone !== '' && Schema::hasColumn('students', 'phone')) {
            $s = Student::query()
                ->whereRaw("REPLACE(COALESCE(phone,''),' ','') = ?", [$phone])
                ->first();
            if ($s) {
                return $s;
            }
        }

        if ($first !== '' && $last !== '' && $birth && Schema::hasColumn('students', 'birth_date')) {
            $s = Student::query()
                ->whereRaw('LOWER(COALESCE(first_name,"")) = ?', [$first])
                ->whereRaw('LOWER(COALESCE(last_name,"")) = ?', [$last])
                ->whereDate('birth_date', $birth)
                ->first();
            if ($s) {
                return $s;
            }
        }

        if ($first !== '' && $last !== '') {
            return Student::query()
                ->whereRaw('LOWER(COALESCE(first_name,"")) = ?', [$first])
                ->whereRaw('LOWER(COALESCE(last_name,"")) = ?', [$last])
                ->orderByDesc('id')
                ->first();
        }

        return null;
    }

    protected static function tryAutoLinkStudentInRepeater(Get $get, Set $set): void
    {
        $state = [
            'student_id'             => $get('student_id'),
            'beneficiary_first_name' => $get('beneficiary_first_name'),
            'beneficiary_last_name'  => $get('beneficiary_last_name'),
            'beneficiary_email'      => $get('beneficiary_email'),
            'beneficiary_phone'      => $get('beneficiary_phone'),
            'beneficiary_birth_date' => $get('beneficiary_birth_date'),
        ];

        if (! empty($state['student_id'])) {
            return;
        }

        $student = static::findStudentForBeneficiary($state);
        if (! $student) {
            $set('auto_match_label', null);
            return;
        }

        $set('student_id', $student->id);

        if (! $get('beneficiary_first_name')) {
            $set('beneficiary_first_name', $student->first_name);
        }
        if (! $get('beneficiary_last_name')) {
            $set('beneficiary_last_name', $student->last_name);
        }
        if (! $get('beneficiary_email')) {
            $set('beneficiary_email', $student->email);
        }
        if (! $get('beneficiary_phone')) {
            $set('beneficiary_phone', $student->phone);
        }

        $set('auto_birth_province', $student->birth_province ?? null);
        $set('auto_match_label', 'Trovato in anagrafica: ' . trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')));
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LessonSlotsRelationManager::class,
        ];
    }
}
