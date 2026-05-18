<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\BillingProfile;
use App\Models\Contract;
use App\Models\ContractStudent;
use App\Models\Installment;
use App\Models\Student;
use App\Services\ContractService;
use App\Services\LessonGeneratorService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateContract extends CreateRecord
{
    protected static string $resource = ContractResource::class;

    protected array $beneficiariesData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validazione Filament-layer: mostra errore prima del salvataggio
        if (! empty($data['starts_at']) && ! empty($data['ends_at'])) {
            $start = \Carbon\Carbon::parse($data['starts_at'])->startOfDay();
            $end   = \Carbon\Carbon::parse($data['ends_at'])->startOfDay();

            if ($end->lt($start)) {
                $this->addError('data.ends_at', 'La data di fine non può essere precedente alla data di inizio.');

                \Filament\Notifications\Notification::make()
                    ->title('Date non valide')
                    ->body('La data di fine corso deve essere successiva alla data di inizio.')
                    ->danger()
                    ->send();

                $this->halt();
            }
        }

        $this->beneficiariesData = $data['beneficiaries'] ?? [];
        unset($data['beneficiaries']);

        // ── Validazione beneficiari ──────────────────────────────────────────
        // 1) Email duplicate tra beneficiari
        $benefEmails = array_filter(
            array_map(fn ($b) => strtolower(trim((string) ($b['beneficiary_email'] ?? ''))), $this->beneficiariesData)
        );
        $emailCounts  = array_count_values($benefEmails);
        $dupEmails    = array_keys(array_filter($emailCounts, fn ($c) => $c > 1));

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
            array_sum(array_map(fn ($b) => (float) ($b['assigned_hours'] ?? 0), $this->beneficiariesData)),
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

        // 2b) Ore FULL non possono superare le ore totali acquistate
        // Senza questo controllo hours_personal diventerebbe negativo (clamped a 0)
        // e nessuna lezione personalizzata verrebbe generata, senza alcun feedback.
        if ($hoursFull > 0 && $hoursTotal > 0 && $hoursFull > $hoursTotal + 0.01) {
            Notification::make()
                ->title('Ore FULL eccedenti')
                ->body("Le ore FULL ({$hoursFull} h) non possono superare le ore totali acquistate ({$hoursTotal} h). Riduci le ore FULL o aumenta le ore totali del contratto.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
        // ────────────────────────────────────────────────────────────────────

        $data['billing_is_student'] = (int) ($data['billing_is_student'] ?? ($data['billing_is_beneficiary'] ?? 0));
        unset($data['billing_is_beneficiary']);

        if (($data['billing_type'] ?? 'private') === 'company') {
            $data['billing_is_student'] = 0;
        }

        if (($data['payment_mode'] ?? 'single') === 'installments' && empty($data['first_installment_date'])) {
            $data['first_installment_date'] = now()->toDateString();
        }

        if (! empty($data['billing_email'])) {
            $data['billing_email'] = Str::lower(trim((string) $data['billing_email']));
        }

        if (! empty($data['company_email'])) {
            $data['company_email'] = Str::lower(trim((string) $data['company_email']));
        }

        if (! empty($data['pec'])) {
            $data['pec'] = Str::lower(trim((string) $data['pec']));
        }

        if (! empty($data['billing_pec'])) {
            $data['billing_pec'] = Str::lower(trim((string) $data['billing_pec']));
        }

        if (($data['billing_type'] ?? 'private') === 'private') {
            $data = app(ContractService::class)->attachOrCreateBillingProfileForPrivate($data);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Contract $contract */
        $contract = $this->record->fresh();
        $contract->loadMissing('course');

        DB::transaction(function () use ($contract) {
            $beneficiaries      = $this->beneficiariesData ?? [];
            $beneficiariesCount = max(1, count($beneficiaries));
            $contractHoursTotal = app(ContractService::class)->resolveContractHoursTotal($contract);

            // Pre-calcola le ore di fallback per i beneficiari senza assigned_hours.
            // Usa floor a 0,5 h e aggiunge il resto all'ultimo, come fa recalcBeneficiariesAssignedHours nel form,
            // per garantire che la somma non superi mai hours_purchased (evita over-generation di lezioni).
            $nullBenefCount    = count(array_filter($beneficiaries, fn ($b) => ! isset($b['assigned_hours']) || $b['assigned_hours'] === '' || (float) $b['assigned_hours'] <= 0));
            $sumExplicit       = round(array_sum(array_map(fn ($b) => (float) ($b['assigned_hours'] ?? 0), array_filter($beneficiaries, fn ($b) => isset($b['assigned_hours']) && $b['assigned_hours'] !== '' && (float) $b['assigned_hours'] > 0))), 2);
            $hoursForNull      = max(0.0, $contractHoursTotal - $sumExplicit);

            // ore base per ogni beneficiario null (floor a 0,5 h)
            $baseHoursPerNull  = $nullBenefCount > 0
                ? floor(($hoursForNull / $nullBenefCount) * 2) / 2
                : 0.0;
            $remainderHours    = $nullBenefCount > 0
                ? round($hoursForNull - ($baseHoursPerNull * $nullBenefCount), 2)
                : 0.0;

            $nullIndex = 0; // contatore per sapere quando siamo all'ultimo beneficiario null

            foreach ($beneficiaries as $b) {
                $email = Str::lower(trim((string) ($b['beneficiary_email'] ?? '')));
                $phone = trim((string) ($b['beneficiary_phone'] ?? ''));

                $studentId = $b['student_id'] ?? null;

                $payload = [
                    'first_name'     => $b['beneficiary_first_name'] ?? null,
                    'last_name'      => $b['beneficiary_last_name'] ?? null,
                    'email'          => $email !== '' ? $email : null,
                    'phone'          => $phone !== '' ? $phone : null,
                    'birth_date'     => $b['beneficiary_birth_date'] ?? null,
                    'birth_place'    => $b['beneficiary_birth_place'] ?? null,
                    'birth_province' => $b['beneficiary_birth_province'] ?? ($b['auto_birth_province'] ?? null),
                    'birth_country'  => $b['beneficiary_birth_country'] ?? null,
                ];

                $studentId = $this->upsertStudentFromBeneficiary($payload, $studentId);

                $hasExplicit   = isset($b['assigned_hours']) && $b['assigned_hours'] !== '' && (float) $b['assigned_hours'] > 0;
                $assignedHours = $hasExplicit ? (float) $b['assigned_hours'] : 0.0;

                if (! $hasExplicit && $contractHoursTotal > 0) {
                    $nullIndex++;
                    $assignedHours = $baseHoursPerNull;
                    // L'ultimo beneficiario senza ore esplicite riceve il resto
                    if ($nullIndex === $nullBenefCount) {
                        $assignedHours = round($assignedHours + $remainderHours, 2);
                    }
                }

                ContractStudent::create([
                    'contract_id' => $contract->id,
                    'student_id'  => $studentId,

                    'beneficiary_first_name' => $b['beneficiary_first_name'] ?? null,
                    'beneficiary_last_name'  => $b['beneficiary_last_name'] ?? null,
                    'beneficiary_email'      => $email !== '' ? $email : null,
                    'beneficiary_phone'      => $phone !== '' ? $phone : null,

                    'beneficiary_birth_date'  => $b['beneficiary_birth_date'] ?? null,
                    'beneficiary_birth_place' => $b['beneficiary_birth_place'] ?? null,

                    'beneficiary_address' => $b['beneficiary_address'] ?? null,
                    'beneficiary_city'    => $b['beneficiary_city'] ?? null,
                    'beneficiary_zip'     => $b['beneficiary_zip'] ?? null,
                    'beneficiary_country' => $b['beneficiary_country'] ?? null,

                    'weekly_day'  => $b['weekly_day'] ?? null,
                    'weekly_time' => $b['weekly_time'] ?? null,
                    'duration_minutes' => max(1, (int) ($b['duration_minutes'] ?? 60)),
                    'teacher_id'  => $b['teacher_id'] ?? null,

                    'assigned_hours' => $assignedHours,

                    'meet_url' => $b['meet_url'] ?? null,
                    'notes'    => $b['notes'] ?? null,
                ]);
            }

            try {
                // ForceDelete permanente: in fase di creazione non ci sono rate pagate,
                // ma usiamo forceDelete per pulizia consistente (bypassa SoftDeletes).
                Installment::where('contract_id', $contract->id)->forceDelete();

                $coursePrice   = (float) $contract->course_price;
                $enrollmentFee = (float) $contract->enrollment_fee;
                $deposit       = (float) $contract->deposit;

                $baseDate = $contract->admission_date
                    ? Carbon::parse($contract->admission_date)
                    : now();

                // Numerazione: -1 = tassa iscrizione, 0 = acconto, 1..n = rate ordinarie.
                // La tassa iscrizione viene sempre fatturata a parte (installment -1).
                // Il residuo da rateizzare riguarda solo il prezzo corso: course_price - deposit.
                // (NON course_price + enrollment_fee - deposit, che causerebbe doppio conteggio.)
                $nextNumber = 1;

                if ($enrollmentFee > 0) {
                    Installment::create([
                        'contract_id' => $contract->id,
                        'number'      => -1,
                        'is_deposit'  => false,
                        'due_date'    => $baseDate->toDateString(),
                        'amount'      => round($enrollmentFee, 2),
                        'status'      => 'unpaid',
                    ]);
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
                }

                // Residuo = prezzo corso − acconto (la tassa iscrizione è già separata).
                $residual = max(0, $coursePrice - $deposit);

                if ($residual <= 0) {
                    return;
                }

                if (($contract->payment_mode ?? 'single') === 'installments') {
                    $count = max(1, (int) ($contract->installments_count ?? 1));

                    $first = $contract->first_installment_date
                        ? Carbon::parse($contract->first_installment_date)
                        : $baseDate->copy()->addDays(15);

                    // floor garantisce che ogni rata non superi il dovuto
                    $base = floor(($residual / $count) * 100) / 100;
                    // La differenza (max ±0.01 × n rate) va sulla PRIMA rata
                    // per evitare che l'ultima rata risulti negativa in edge case
                    $firstAmount = round($residual - ($base * ($count - 1)), 2);

                    $firstInstallmentNumber = $nextNumber;

                    for ($i = 0; $i < $count; $i++) {
                        Installment::create([
                            'contract_id' => $contract->id,
                            'number'      => $firstInstallmentNumber + $i,
                            'is_deposit'  => false,
                            'due_date'    => $first->copy()->addMonths($i)->toDateString(),
                            'amount'      => ($i === 0) ? $firstAmount : $base,
                            'status'      => 'unpaid',
                        ]);
                    }
                } else {
                    $due = $contract->first_installment_date
                        ? Carbon::parse($contract->first_installment_date)
                        : $baseDate->copy()->addDays(15);

                    Installment::create([
                        'contract_id' => $contract->id,
                        'number'      => $nextNumber,
                        'is_deposit'  => false,
                        'due_date'    => $due->toDateString(),
                        'amount'      => round($residual, 2),
                        'status'      => 'unpaid',
                    ]);
                }
            } catch (QueryException $e) {
                report($e);

                Notification::make()
                    ->title('Errore nella generazione dei pagamenti')
                    ->body('Non è stato possibile salvare tassa/acconto/rate. Controlla i valori inseriti e riprova.')
                    ->danger()
                    ->persistent()
                    ->send();

                throw $e;
            } catch (\Throwable $e) {
                report($e);

                Notification::make()
                    ->title('Errore nella generazione dei pagamenti')
                    ->body('Si è verificato un errore inatteso durante la creazione delle rate.')
                    ->danger()
                    ->persistent()
                    ->send();

                throw $e;
            }
        });

        $contractId = (int) $contract->id;

        DB::afterCommit(function () use ($contractId) {
            try {
                $contract = Contract::query()
                    ->with(['beneficiaries', 'course'])
                    ->findOrFail($contractId);

                // Gli slot vengono già creati da Contract::syncLessonSlotsFromBeneficiaries()
                // invocato nel hook Contract::saved() → non duplichiamo qui la logica.
                // Forziamo solo la prima generazione completa (force=true) delle lezioni
                // partendo da starts_at, che include anche eventuali date nel passato.
                app(LessonGeneratorService::class)->generateForContract($contract->fresh(), true);
            } catch (\Throwable $e) {
                report($e);

                Notification::make()
                    ->title('Contratto salvato, ma generazione lezioni non completata')
                    ->body('Le lezioni non sono state generate correttamente. Usa "Genera / completa lezioni" dal contratto.')
                    ->warning()
                    ->send();
            }
        });
    }

    // resolveContractHoursTotal e attachOrCreateBillingProfileForPrivate
    // sono stati spostati in App\Services\ContractService (condivisi con EditContract).

    private function upsertStudentFromBeneficiary(array $data, ?int $studentId = null): ?int
    {
        $student = null;

        if (! empty($studentId)) {
            $student = Student::find((int) $studentId);
        }

        $email = Str::lower(trim((string) ($data['email'] ?? '')));
        $phone = preg_replace('/\s+/', '', (string) ($data['phone'] ?? ''));
        $first = trim((string) ($data['first_name'] ?? ''));
        $last  = trim((string) ($data['last_name'] ?? ''));

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
                return null;
            }

            $student = new Student();
        }

        $fill = [];

        if (Schema::hasColumn('students', 'first_name') && $first !== '') {
            $fill['first_name'] = $first;
        }

        if (Schema::hasColumn('students', 'last_name') && $last !== '') {
            $fill['last_name'] = $last;
        }

        if (Schema::hasColumn('students', 'email') && $email !== '') {
            $fill['email'] = $email;
        }

        if (Schema::hasColumn('students', 'phone') && $phone !== '') {
            $fill['phone'] = $phone;
        }

        if (Schema::hasColumn('students', 'birth_date') && ! empty($data['birth_date'])) {
            $fill['birth_date'] = $data['birth_date'];
        }

        if (Schema::hasColumn('students', 'birth_place') && ! empty($data['birth_place'])) {
            $fill['birth_place'] = $data['birth_place'];
        }

        if (Schema::hasColumn('students', 'birth_province') && ! empty($data['birth_province'])) {
            $fill['birth_province'] = $data['birth_province'];
        }

        if (Schema::hasColumn('students', 'birth_country') && ! empty($data['birth_country'])) {
            $fill['birth_country'] = $data['birth_country'];
        }

        $student->fill($fill);
        $student->save();

        return (int) $student->id;
    }
}
