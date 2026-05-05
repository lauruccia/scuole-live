<?php

namespace App\Filament\Pages;

use App\Mail\StudentCommunicationMail;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;

class InvioComunicazioni extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Invio comunicazioni';
    protected static ?string $title = 'Invio comunicazioni';
    protected static ?string $navigationGroup = 'Comunicazioni';
    protected static string $view = 'filament.pages.invio-comunicazioni';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['Amministrazione', 'Segreteria', 'super_admin']);
        }

        return false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'recipient_mode' => 'all',
            'student_ids' => [],
            'subject' => '',
            'body' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Destinatari')
                    ->schema([
                        Forms\Components\Radio::make('recipient_mode')
                            ->label('Invia a')
                            ->options([
                                'all' => 'Tutti gli studenti',
                                'manual' => 'Selezione manuale',
                            ])
                            ->default('all')
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('student_ids')
                            ->label('Studenti')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn () => Student::query()
                                ->orderBy('last_name')
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(fn ($student) => [
                                    $student->id => trim(($student->last_name ?? '') . ' ' . ($student->first_name ?? '')) . ' - ' . ($student->email ?? 'senza email'),
                                ])
                                ->toArray())
                            ->visible(fn (Forms\Get $get) => $get('recipient_mode') === 'manual')
                            ->required(fn (Forms\Get $get) => $get('recipient_mode') === 'manual'),
                    ]),

                Forms\Components\Section::make('Messaggio')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label('Oggetto')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\RichEditor::make('body')
                            ->label('Messaggio')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                                'link',
                                'redo',
                                'undo',
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $state = $this->form->getState();

        $query = Student::query()->whereNotNull('email')->where('email', '!=', '');

        if (($state['recipient_mode'] ?? 'all') === 'manual') {
            $ids = $state['student_ids'] ?? [];

            if (empty($ids)) {
                Notification::make()
                    ->title('Seleziona almeno uno studente')
                    ->danger()
                    ->send();

                return;
            }

            $query->whereIn('id', $ids);
        }

        $students = $query->get();

        if ($students->isEmpty()) {
            Notification::make()
                ->title('Nessuno studente con email valida trovato')
                ->warning()
                ->send();

            return;
        }

        // Filtro disiscritti GDPR (lookup bulk per evitare N+1)
        $unsubscribed = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('student_unsubscribes')) {
            $unsubscribed = collect(
                \Illuminate\Support\Facades\DB::table('student_unsubscribes')
                    ->whereIn('email', $students->pluck('email')->filter()->all())
                    ->pluck('email')
                    ->map(fn ($e) => strtolower($e))
                    ->all()
            );
        }

        $sent    = 0;
        $errors  = 0;
        $skipped = 0;

        foreach ($students as $student) {
            // Skip disiscritti GDPR
            if ($unsubscribed->contains(strtolower($student->email))) {
                $skipped++;
                continue;
            }

            try {
                // Mail::queue invia in coda asincrona (no blocking della richiesta)
                // Il worker queue:work processa effettivamente l'invio.
                Mail::to($student->email)->queue(
                    new StudentCommunicationMail(
                        studentName: trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')),
                        subjectLine: $state['subject'],
                        htmlBody: $state['body'],
                    )
                );

                $sent++;
            } catch (\Throwable $e) {
                $errors++;
                report($e);
            }
        }

        $detail = "Email accodate: {$sent}";
        if ($skipped > 0) $detail .= " | Disiscritti saltati: {$skipped}";
        if ($errors > 0)  $detail .= " | Errori: {$errors}";

        Notification::make()
            ->title($detail)
            ->success()
            ->send();

        $this->form->fill([
            'recipient_mode' => 'all',
            'student_ids' => [],
            'subject' => '',
            'body' => '',
        ]);
    }
}