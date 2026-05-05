<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Filament\Facades\Filament;

class SuperadminCommands extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';
    protected static ?string $navigationGroup = 'Impostazioni';
    protected static ?string $navigationLabel = 'Comandi';
    protected static ?string $title = 'Comandi (Superadmin)';
    protected static string $view = 'filament.pages.superadmin-commands';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $u = Filament::auth()->user();

        return $u && $u->hasRole('superadmin');
    }

    public static function canAccess(): bool
    {
        $u = Filament::auth()->user();

        return $u && $u->hasRole('superadmin');
    }

    /**
     * Catalogo comandi: aggiungi qui i prossimi.
     * La UI si aggiorna da sola.
     */
    public function getCommandsCatalog(): array
    {
        return [
            [
                'key' => 'clear_cache',
                'title' => 'Svuota cache',
                'subtitle' => 'optimize:clear + filament:cache-components --clear + permission:cache-reset',
                'description' => 'Pulisce TUTTA la cache (config, view, route, event, application, Filament components, Spatie permissions). Da usare quando il menu admin non rispecchia il codice deployato (voci mancanti dopo un deploy). Opzionalmente ricrea la cache di produzione.',
                'tone' => 'warning',
                'requires_confirmation' => true,
            ],
            [
                'key' => 'bonifica_consumi',
                'title' => 'Bonifica ore fruite',
                'subtitle' => 'scuole:bonifica',
                'description' => 'Riallinea flags delle lezioni (counts_as_consumed / is_recoverable) e ricalcola hours_consumed sui contratti. Usalo dopo import o valori “Fruite” errati.',
                'tone' => 'success',
                'requires_confirmation' => true,
            ],
            [
                'key' => 'backfill_billing',
                'title' => 'Backfill Billing Profiles',
                'subtitle' => 'billing:backfill',
                'description' => 'Crea/aggancia Company e BillingProfile dai dati storici presenti in contracts e collega company_id / billing_profile_id.',
                'tone' => 'gray',
                'requires_confirmation' => true,
            ],
            [
                'key' => 'fix_future_lessons',
                'title' => 'Fix lezioni future',
                'subtitle' => 'lessons:fix-future-counts',
                'description' => 'Corregge lezioni future non annullate che risultano “consumate” erroneamente (counts_as_consumed=1) riportandole a 0.',
                'tone' => 'warning',
                'requires_confirmation' => true,
            ],
            [
                'key' => 'regenerate_lessons',
                'title' => 'Rigenera lezioni',
                'subtitle' => 'lessons:regenerate-all',
                'description' => 'Rigenera/completa lezioni per i contratti (LessonGeneratorService). Operazione pesante: usare con cautela.',
                'tone' => 'danger',
                'requires_confirmation' => true,
            ],
        ];
    }

    public function toneClasses(string $tone): array
    {
        return match ($tone) {
            'success' => [
                'card' => 'border-success-200',
                'badge' => 'bg-success-50 text-success-700 ring-success-600/20',
            ],
            'warning' => [
                'card' => 'border-warning-200',
                'badge' => 'bg-warning-50 text-warning-700 ring-warning-600/20',
            ],
            'danger' => [
                'card' => 'border-danger-200',
                'badge' => 'bg-danger-50 text-danger-700 ring-danger-600/20',
            ],
            default => [
                'card' => 'border-gray-200',
                'badge' => 'bg-gray-50 text-gray-700 ring-gray-600/20',
            ],
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear_cache')
                ->label('Svuota cache')
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Svuota tutta la cache')
                ->modalDescription('Esegue optimize:clear, view:clear, cache:clear, filament:cache-components --clear, permission:cache-reset. Da usare quando il menu non riflette il codice deployato.')
                ->form([
                    Toggle::make('rebuild')
                        ->label('Rigenera cache produzione dopo (raccomandato)')
                        ->helperText('Esegue optimize + filament:optimize + view:cache subito dopo la pulizia.')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    $output = [];
                    $tryCall = function (string $cmd, array $params = []) use (&$output) {
                        try {
                            Artisan::call($cmd, $params);
                            $output[] = "✓ {$cmd}";
                        } catch (\Throwable $e) {
                            $output[] = "✗ {$cmd}: " . $e->getMessage();
                        }
                    };

                    // Clear di tutte le cache standard
                    $tryCall('optimize:clear');
                    $tryCall('view:clear');
                    $tryCall('config:clear');
                    $tryCall('cache:clear');
                    $tryCall('route:clear');
                    $tryCall('event:clear');
                    $tryCall('filament:cache-components', ['--clear' => true]);

                    // Cache permessi Spatie (separata, non gestita da optimize:clear)
                    if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
                        try {
                            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
                            $output[] = '✓ permission cache reset';
                        } catch (\Throwable $e) {
                            $output[] = '✗ permission cache: ' . $e->getMessage();
                        }
                    }

                    if (! empty($data['rebuild'])) {
                        $tryCall('optimize');
                        $tryCall('view:cache');
                        $tryCall('event:cache');
                        $tryCall('filament:optimize');
                    }

                    Notification::make()
                        ->title('Cache svuotata' . (! empty($data['rebuild']) ? ' e rigenerata' : ''))
                        ->body(implode("\n", $output))
                        ->success()
                        ->persistent()
                        ->send();
                }),

            Action::make('bonifica_consumi')
                ->label('Bonifica ore fruite')
                ->icon('heroicon-o-wrench-screwdriver')
                ->requiresConfirmation()
                ->modalHeading('Bonifica ore fruite')
                ->modalDescription('Riallinea flags delle lezioni e ricalcola hours_consumed sui contratti.')
                ->form([
                    Section::make('Opzioni')
                        ->schema([
                            TextInput::make('contract')->label('Solo Contract ID (opzionale)')->numeric(),
                            DatePicker::make('from')->label('Da data (opzionale)'),
                            DatePicker::make('to')->label('A data (opzionale)'),
                            Toggle::make('dry')->label('Dry-run (simula, non salva)')->default(false),
                        ])->columns(2),
                ])
                ->action(function (array $data) {
                    $params = ['--chunk' => 300];

                    if (! empty($data['contract'])) $params['--contract'] = (int) $data['contract'];
                    if (! empty($data['from']))     $params['--from'] = $data['from'];
                    if (! empty($data['to']))       $params['--to'] = $data['to'];
                    if (! empty($data['dry']))      $params['--dry'] = true;

                    Artisan::call('scuole:bonifica', $params);

                    Notification::make()
                        ->title('Bonifica completata')
                        ->body('Ora la colonna "Fruite" dovrebbe risultare corretta.')
                        ->success()
                        ->send();
                }),

            Action::make('backfill_billing')
                ->label('Backfill Billing Profiles')
                ->icon('heroicon-o-identification')
                ->requiresConfirmation()
                ->modalHeading('Backfill Billing Profiles')
                ->modalDescription('Crea/aggancia Company e BillingProfile dai dati storici dei contratti.')
                ->form([
                    Toggle::make('dry_run')->label('Dry-run (simula, non salva)')->default(false),
                ])
                ->action(function (array $data) {
                    $params = [];
                    if (! empty($data['dry_run'])) $params['--dry-run'] = true;

                    Artisan::call('billing:backfill', $params);

                    Notification::make()
                        ->title('Backfill completato')
                        ->success()
                        ->send();
                }),

            Action::make('fix_future_lessons')
                ->label('Fix lezioni future')
                ->icon('heroicon-o-calendar-days')
                ->requiresConfirmation()
                ->modalHeading('Fix lezioni future')
                ->modalDescription('Imposta counts_as_consumed=0 sulle lezioni future non annullate che risultano consumate.')
                ->form([
                    Toggle::make('dry_run')->label('Dry-run (simula, non salva)')->default(false),
                ])
                ->action(function (array $data) {
                    $params = [];
                    if (! empty($data['dry_run'])) $params['--dry-run'] = true;

                    Artisan::call('lessons:fix-future-counts', $params);

                    Notification::make()
                        ->title('Fix completato')
                        ->success()
                        ->send();
                }),

            Action::make('regenerate_lessons')
                ->label('Rigenera lezioni')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Rigenerare lezioni?')
                ->modalDescription('Operazione pesante: rigenera/completa le lezioni per tutti i contratti. Usare solo se necessario.')
                ->form([
                    Section::make('Opzioni')
                        ->schema([
                            TextInput::make('chunk')->label('Chunk contratti')->numeric()->default(200),
                            TextInput::make('from_id')->label('Da Contract ID (opzionale)')->numeric(),
                            TextInput::make('to_id')->label('A Contract ID (opzionale)')->numeric(),
                            Toggle::make('dry_run')->label('Dry-run (simula, non salva)')->default(false),
                            Toggle::make('force')->label('Force (starts_at anche nel passato)')->default(false),
                        ])->columns(2),
                ])
                ->action(function (array $data) {
                    $params = [
                        '--chunk' => (int) ($data['chunk'] ?? 200),
                    ];

                    if (! empty($data['from_id'])) $params['--from_id'] = (int) $data['from_id'];
                    if (! empty($data['to_id']))   $params['--to_id'] = (int) $data['to_id'];
                    if (! empty($data['dry_run'])) $params['--dry-run'] = true;
                    if (! empty($data['force']))   $params['--force'] = true;

                    Artisan::call('lessons:regenerate-all', $params);

                    Notification::make()
                        ->title('Rigenerazione completata')
                        ->warning()
                        ->send();
                }),
        ];
    }
}
