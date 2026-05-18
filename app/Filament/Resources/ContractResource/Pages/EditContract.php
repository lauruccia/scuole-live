<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\BillingProfile;
use App\Models\Contract;
use App\Services\ContractService;
use App\Models\ContractLessonSlot;
use App\Models\Installment;
use App\Models\Student;
use App\Services\FullLessonService;
use App\Services\LessonGeneratorService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EditContract extends EditRecord
{
    protected static string $resource = ContractResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validazione Filament-layer: mostra errore in-form prima del salvataggio
        if (! empty($data['starts_at']) && ! empty($data['ends_at'])) {
            $start = \Carbon\Carbon::parse($data['starts_at'])->startOfDay();
            $end   = \Carbon\Carbon::parse($data['ends_at'])->startOfDay();

            if ($end->lt($start)) {
                $this->addError('data.ends_at', 'La data di fine non può essere precedente alla data di inizio.');

                Notification::make()
                    ->title('Date non valide')
                    ->body('La data di fine corso deve essere successiva alla data di inizio.')
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        if (! empty($data['billing_email'])) {
            $data['billing_email'] = Str::lower(trim((string) $data['billing_email']));
        }

        if (! empty($data['pec'])) {
            $data['pec'] = Str::lower(trim((string) $data['pec']));
        }

        if (! empty($data['billing_pec'])) {
            $data['billing_pec'] = Str::lower(trim((string) $data['billing_pec']));
        }

        // ── Validazione beneficiari ──────────────────────────────────────────
        $beneficiaries = $data['beneficiaries'] ?? [];

        // 1) Email duplicate tra beneficiari
        $benefEmails = array_filter(
            array_map(fn ($b) => strtolower(trim((string) ($b['beneficiary_email'] ?? ''))), $beneficiaries)
        );
        $emailCounts = array_count_values($benefEmails);
        $dupEmails   = array_keys(array_filter($emailCounts, fn ($c) => $c > 1));

        if (! empty($dupEmails)) {
            Notification::make()
                ->title('Email duplicate nei beneficiari')
                ->body('I seguenti indirizzi email appaiono più volte tra i beneficiari: ' . implode(', ', $dupEmails) . '. Ogni beneficiario deve avere un\'email univoca.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        // 2) Ore assegnate > ore personalizzate (totale - full)
        $hoursTotal    = round((float) ($data['hours_purchased'] ?? 0), 2);
        $hoursFull     = round((float) ($data['hours_full']      ?? 0), 2);
        $hoursPersonal = max(0.0, $hoursTotal - $hoursFull);
        $totalAssigned = round(
            array_sum(array_map(fn ($b) => (float) ($b['assigned_hours'] ?? 0), $beneficiaries)),
            2
        );

        if ($hoursPersonal > 0 && $totalAssigned > $hoursPersonal + 0.01) {
            Notification::make()
                ->title('Ore assegnate eccedenti')
                ->body("Le ore assegnate ai beneficiari ({$totalAssigned} h) superano le ore personalizzate del contratto ({$hoursPersonal} h). Correggi la distribuzione prima di salvare.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
        // ────────────────────────────────────────────────────────────────────

        // 3) Validazione ore FULL (solo per contratti MIX)
        if (($data['lesson_type'] ?? '') === 'Lezioni personalizzate + FULL') {
            $hoursFull        = round((float) ($data['hours_full'] ?? 0), 2);
            $totalFullAssigned = round(
                array_sum(array_map(fn ($b) => (float) ($b['assigned_hours_full'] ?? 0), $beneficiaries)),
                2
            );

            // Se almeno un beneficiario ha ore FULL configurate, la somma deve corrispondere
            $hasAnyFullConfigured = array_filter($beneficiaries, fn ($b) => ($b['assigned_hours_full'] ?? null) !== null);
            if (! empty($hasAnyFullConfigured) && $hoursFull > 0 && abs($totalFullAssigned - $hoursFull) > 0.01) {
                Notification::make()
                    ->title('Ore FULL non bilanciate')
                    ->body("La somma delle ore FULL assegnate ai beneficiari ({$totalFullAssigned} h) non corrisponde alle ore FULL del contratto ({$hoursFull} h). Correggi la distribuzione prima di salvare.")
                    ->danger()
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }
        // ────────────────────────────────────────────────────────────────────

        $data['billing_is_student'] = (int) ($data['billing_is_student'] ?? ($data['billing_is_beneficiary'] ?? 0));
        unset($data['billing_is_beneficiary']);

        if (($data['billing_type'] ?? 'private') === 'company') {
            $data['billing_is_student'] = 0;
        }

        if (($data['billing_type'] ?? 'private') === 'private') {
            $data = app(ContractService::class)->attachOrCreateBillingProfileForPrivate($data);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_lessons_safe')
                ->label('Completa lezioni')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->visible(fn (): bool => $this->canManageLessons())
                ->requiresConfirmation()
                ->modalHeading('Genera / completa lezioni')
                ->modalDescription('Genera le lezioni in base agli slot attivi, senza cancellare quelle future già presenti (se non confliggono).')
                ->action(function (): void {
                    $ok = $this->runLocked('generateLessonsSafe', function (): int {
                        /** @var Contract $contract */
                        $contract = $this->record->fresh();
                        app(LessonGeneratorService::class)->generateForContract($contract, false);

                        return 1;
                    });

                    if ($ok) {
                        Notification::make()
                            ->title('Lezioni generate correttamente')
                            ->success()
                            ->send();
                    }
                }),

            Actions\Action::make('regenerate_lessons_future')
                ->label('Rigenera lezioni')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->visible(fn (): bool => $this->canManageLessons())
                ->requiresConfirmation()
                ->modalHeading('Rigenera lezioni (cancella future)')
                ->modalDescription('Elimina le lezioni future NON svolte e le rigenera in base agli slot attivi.')
                ->action(function (): void {
                    $ok = $this->runLocked('regenerateLessonsForce', function (): int {
                        /** @var Contract $contract */
                        $contract = $this->record->fresh();
                        app(LessonGeneratorService::class)->generateForContract($contract, true);

                        return 1;
                    });

                    if ($ok) {
                        Notification::make()
                            ->title('Lezioni rigenerate correttamente')
                            ->success()
                            ->send();
                    }
                }),

            Actions\Action::make('generate_full_placeholders')
                ->label('Genera lezioni FULL')
                ->icon('heroicon-o-user-group')
                ->color('info')
                ->visible(fn (): bool => $this->canManageLessons()
                    && ($this->record->lesson_type ?? '') === 'Lezioni personalizzate + FULL')
                ->requiresConfirmation()
                ->modalHeading('Genera lezioni FULL')
                ->modalDescription('Distribuisce le ore FULL tra i beneficiari (se non ancora assegnate) e aggiunge le lezioni FULL mancanti. Le lezioni già pianificate, completate o annullate non vengono modificate.')
                ->action(function (): void {
                    $ok = $this->runLocked('generateFullPlaceholders', function (): int {
                        $contract = $this->record->fresh();
                        $service  = app(FullLessonService::class);
                        $contract->load('beneficiaries');

                        $service->distributeFullHours($contract);
                        $contract->load('beneficiaries');
                        $service->generatePlaceholders($contract);

                        return 1;
                    });

                    if ($ok) {
                        Notification::make()
                            ->title('Lezioni FULL generate correttamente')
                            ->success()
                            ->send();
                    }
                }),

            Actions\Action::make('regenerate_installments')
                ->label('Rigenera scadenze')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->visible(fn (): bool => $this->canManagePayments())
                ->requiresConfirmation()
                ->modalHeading('Rigenera scadenze e pagamenti')
                ->modalDescription('Ricrea: Tassa iscrizione, Acconto e Saldo/Rate in base ai campi del contratto.')
                ->action(function (): void {
                    try {
                        $created = $this->runLocked('regenerateInstallments', function (): int {
                            /** @var Contract $contract */
                            $contract = $this->record->fresh();

                            return $this->rebuildInstallments($contract);
                        });

                        Notification::make()
                            ->title('Scadenze rigenerate')
                            ->body("Create {$created} scadenze/pagamenti.")
                            ->success()
                            ->send();
                    } catch (\RuntimeException $e) {
                        Notification::make()
                            ->title('Impossibile rigenerare le scadenze')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            Actions\DeleteAction::make()
                ->label('Elimina contratto')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->visible(fn (): bool => $this->canManageLessons())
                ->requiresConfirmation()
                ->modalHeading('Elimina contratto')
                ->modalDescription(
                    'Questa operazione sposta il contratto nel cestino insieme a tutte le sue lezioni. ' .
                    'Le rate rimangono visibili nei report. ' .
                    'Il contratto può essere ripristinato dal superadmin. ' .
                    'Sei sicuro di voler continuare?'
                )
                ->modalSubmitActionLabel('Sì, elimina')
                ->modalCancelActionLabel('Annulla'),
        ];
    }

    // attachOrCreateBillingProfileForPrivate e resolveContractHoursTotal
    // sono stati spostati in App\Services\ContractService (condivisi con CreateContract).

    private function canManageLessons(): bool
    {
        $u = Auth::user();

        return (bool) ($u?->hasAnyRole([
            'superadmin',
            'amministrazione',
            'segreteria',
            'Amministrazione',
            'Segreteria',
        ]) ?? false);
    }

    private function canManagePayments(): bool
    {
        return $this->canManageLessons();
    }

    private function rebuildInstallments(Contract $contract): int
    {
        // Blocca la rigenerazione se esistono rate già pagate:
        // eliminarle comporterebbe perdita irrecuperabile di dati finanziari.
        $paidCount = Installment::where('contract_id', $contract->id)
            ->whereNotNull('paid_at')
            ->count();

        if ($paidCount > 0) {
            throw new \RuntimeException(
                "Impossibile rigenerare le scadenze: {$paidCount} rata/e risultano già pagate. " .
                "Elimina manualmente le rate pagate prima di rigenerare."
            );
        }

        // ForceDelete permanente (bypassa SoftDeletes) per le rate non pagate,
        // così non si accumulano record soft-deleted inutili.
        Installment::where('contract_id', $contract->id)->forceDelete();

        $coursePrice   = (float) $contract->course_price;
        $enrollmentFee = (float) $contract->enrollment_fee;
        $deposit       = (float) $contract->deposit;

        $baseDate = $contract->admission_date
            ? Carbon::parse($contract->admission_date)
            : now();

        $created = 0;

        if ($enrollmentFee > 0) {
            Installment::create([
                'contract_id' => $contract->id,
                'number'      => -1,
                'is_deposit'  => false,
                'due_date'    => $baseDate->toDateString(),
                'amount'      => round($enrollmentFee, 2),
                'status'      => 'unpaid',
            ]);
            $created++;
        }

        if ($deposit > 0) {
            Installment::create([
                'contract_id' => $contract->id,
                'number'      => 0,
                'is_deposit'  => true,
                'due_date'    => $baseDate->toDateString(),
                'amount'      => round($deposit, 2),
                'status'      => 'unpaid',
            ]);
            $created++;
        }

        // Residuo = prezzo corso − acconto (la tassa iscrizione è già separata come installment -1).
        $residual = max(0, $coursePrice - $deposit);
        if ($residual <= 0) {
            return $created;
        }

        if (($contract->payment_mode ?? 'single') === 'installments') {
            $count = max(1, (int) ($contract->installments_count ?? 1));

            $first = $contract->first_installment_date
                ? Carbon::parse($contract->first_installment_date)
                : $baseDate->copy()->addDays(15);

            $base = floor(($residual / $count) * 100) / 100;
            // La differenza va sulla PRIMA rata per evitare edge case con diff negativo
            $firstAmount = round($residual - ($base * ($count - 1)), 2);

            for ($i = 1; $i <= $count; $i++) {
                Installment::create([
                    'contract_id' => $contract->id,
                    'number'      => $i,
                    'is_deposit'  => false,
                    'due_date'    => $first->copy()->addMonths($i - 1)->toDateString(),
                    'amount'      => ($i === 1) ? $firstAmount : $base,
                    'status'      => 'unpaid',
                ]);

                $created++;
            }

            return $created;
        }

        $due = $contract->first_installment_date
            ? Carbon::parse($contract->first_installment_date)
            : $baseDate->copy()->addDays(15);

        Installment::create([
            'contract_id' => $contract->id,
            'number'      => 1,
            'is_deposit'  => false,
            'due_date'    => $due->toDateString(),
            'amount'      => round($residual, 2),
            'status'      => 'unpaid',
        ]);
        $created++;

        return $created;
    }

    private function runLocked(string $action, \Closure $callback): int
    {
        $contractId = (int) ($this->record?->getKey() ?? 0);

        $lock = Cache::lock("contract_action:{$contractId}:{$action}", 20);

        if (! $lock->get()) {
            Notification::make()
                ->title('Operazione già in corso')
                ->body('Attendi qualche secondo e riprova.')
                ->warning()
                ->send();

            return 0;
        }

        try {
            return (int) $callback();
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title("Errore durante l’operazione")
                ->body('Operazione non completata: ' . $e->getMessage() . ' - Riprova o contatta la segreteria.')
                ->danger()
                ->send();

            return 0;
        } finally {
            $lock->release();
        }
    }

    protected function afterSave(): void
    {
        /** @var Contract $contract */
        $contract = $this->record->fresh();
        $contract->load(['beneficiaries', 'course']);

        $beneficiaries = $contract->beneficiaries;
        $beneficiariesCount = max(1, $beneficiaries->count());
        $contractHoursTotal = app(ContractService::class)->resolveContractHoursTotal($contract);

        foreach ($beneficiaries as $beneficiary) {
            $student = null;

            if ($beneficiary->student_id) {
                $student = Student::find($beneficiary->student_id);
            }

            $email = Str::lower(trim((string) ($beneficiary->beneficiary_email ?? '')));
            $phone = preg_replace('/\s+/', '', (string) ($beneficiary->beneficiary_phone ?? ''));
            $first = trim((string) ($beneficiary->beneficiary_first_name ?? ''));
            $last  = trim((string) ($beneficiary->beneficiary_last_name ?? ''));

            if (! $student && $email !== '') {
                $student = Student::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->first();
            }

            if (! $student && $phone !== '') {
                $student = Student::query()
                    ->whereRaw("REPLACE(COALESCE(phone,''),' ','') = ?", [$phone])
                    ->first();
            }

            if (! $student && $first !== '' && $last !== '') {
                $student = Student::query()
                    ->whereRaw('LOWER(COALESCE(first_name,"")) = ?', [Str::lower($first)])
                    ->whereRaw('LOWER(COALESCE(last_name,"")) = ?', [Str::lower($last)])
                    ->first();
            }

            if (! $student) {
                if ($first === '' && $last === '') {
                    continue;
                }

                $student = new Student();
            }

            $fill = [];

            if ($first !== '') {
                $fill['first_name'] = $first;
            }

            if ($last !== '') {
                $fill['last_name'] = $last;
            }

            if ($email !== '') {
                $fill['email'] = $email;
            }

            if ($phone !== '') {
                $fill['phone'] = $phone;
            }

            if (! empty($beneficiary->beneficiary_birth_date)) {
                $fill['birth_date'] = $beneficiary->beneficiary_birth_date;
            }

            if (! empty($beneficiary->beneficiary_birth_place)) {
                $fill['birth_place'] = $beneficiary->beneficiary_birth_place;
            }

            $student->fill($fill);
            $student->save();

            if ((int) ($beneficiary->student_id ?? 0) !== (int) $student->id) {
                $beneficiary->student_id = $student->id;
            }

            $assignedHours = (float) ($beneficiary->assigned_hours ?? 0);

            if ($assignedHours <= 0 && $contractHoursTotal > 0) {
                $assignedHours = $beneficiariesCount === 1
                    ? $contractHoursTotal
                    : round($contractHoursTotal / $beneficiariesCount, 2);

                $beneficiary->assigned_hours = $assignedHours;
            }

            $beneficiary->save();

            if ($beneficiary->student_id && $beneficiary->weekly_day && $beneficiary->weekly_time) {
                ContractLessonSlot::updateOrCreate(
                    [
                        'contract_id' => $contract->id,
                        'student_id'  => (int) $beneficiary->student_id,
                        'weekly_day'  => (int) $beneficiary->weekly_day,
                        'weekly_time' => $beneficiary->weekly_time,
                    ],
                        [
                            'teacher_id'       => $beneficiary->teacher_id,
                            'duration_minutes' => max(1, (int) ($beneficiary->duration_minutes ?? 60)),
                            'is_active'        => true,
                        'starts_at'        => $contract->starts_at
                            ? Carbon::parse($contract->starts_at)->toDateString()
                            : null,
                        'ends_at'          => null,
                        'meet_url'         => $beneficiary->meet_url,
                    ]
                );
            }
        }

        try {
            app(LessonGeneratorService::class)->generateForContract($contract->fresh(), true);
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Contratto salvato, ma lezioni non rigenerate')
                ->body('Controlla assigned_hours, starts_at e gli slot attivi del contratto.')
                ->warning()
                ->send();
        }

        // ── Gestione lezioni FULL (contratti MIX) ───────────────────────────
        $freshContract = $contract->fresh();
        if (FullLessonService::isMixContract($freshContract)) {
            try {
                $fullService = app(FullLessonService::class);

                // Distribuzione automatica se i beneficiari non hanno ancora ore FULL configurate
                $freshContract->load('beneficiaries');
                $anyFullMissing = $freshContract->beneficiaries
                    ->contains(fn ($cs) => is_null($cs->assigned_hours_full) || (float) $cs->assigned_hours_full <= 0);

                if ($anyFullMissing) {
                    $fullService->distributeFullHours($freshContract);
                    $freshContract->load('beneficiaries'); // ricarica dopo la distribuzione
                }

                // Genera i segnaposto mancanti
                $fullService->generatePlaceholders($freshContract);
            } catch (\Throwable $e) {
                report($e);

                Notification::make()
                    ->title('Lezioni FULL non generate')
                    ->body('Verifica le ore FULL assegnate ai beneficiari del contratto. Dettaglio: ' . $e->getMessage())
                    ->warning()
                    ->send();
            }
        }
        // ────────────────────────────────────────────────────────────────────
    }

}
