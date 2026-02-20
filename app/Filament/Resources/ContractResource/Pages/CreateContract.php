<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\BillingProfile;
use App\Models\Contract;
use App\Models\ContractLessonSlot;
use App\Models\ContractStudent;
use App\Models\Installment;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateContract extends CreateRecord
{
    protected static string $resource = ContractResource::class;

    /**
     * Dati repeater beneficiari (salvati prima della create, poi inseriti manualmente in contract_students)
     * @var array<int, array<string, mixed>>
     */
    protected array $beneficiariesData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->beneficiariesData = $data['beneficiaries'] ?? [];
        unset($data['beneficiaries']);

        // ✅ compat: non usare più billing_is_beneficiary
        $data['billing_is_student'] = (int) ($data['billing_is_student'] ?? ($data['billing_is_beneficiary'] ?? 0));
        unset($data['billing_is_beneficiary']);

        // azienda: mai "pagante = studente"
        if (($data['billing_type'] ?? 'private') === 'company') {
            $data['billing_is_student'] = 0;
        }

        // default data prima rata se modalità rate
        if (($data['payment_mode'] ?? 'single') === 'installments' && empty($data['first_installment_date'])) {
            $data['first_installment_date'] = now()->toDateString();
        }

        // normalizzo email
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

        /**
         * ✅ FIX: se PRIVATO e non scelgo billing_profile_id ma compilo billing_*,
         * crea/riusa BillingProfile e assegna billing_profile_id
         */
        if (($data['billing_type'] ?? 'private') === 'private') {
            $data = $this->attachOrCreateBillingProfileForPrivate($data);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Contract $contract */
        $contract = $this->record->fresh();

        DB::transaction(function () use ($contract) {

            // 1) Beneficiari (+ creazione automatica Student se manca)
            foreach (($this->beneficiariesData ?? []) as $b) {
                $email = Str::lower(trim((string) ($b['beneficiary_email'] ?? '')));
                $phone = trim((string) ($b['beneficiary_phone'] ?? ''));

                $studentId = $b['student_id'] ?? null;

                if (empty($studentId)) {
                    $studentId = $this->upsertStudentFromBeneficiary([
                        'first_name'     => $b['beneficiary_first_name'] ?? null,
                        'last_name'      => $b['beneficiary_last_name'] ?? null,
                        'email'          => $email !== '' ? $email : null,
                        'phone'          => $phone !== '' ? $phone : null,
                        'birth_date'     => $b['beneficiary_birth_date'] ?? null,
                        'birth_place'    => $b['beneficiary_birth_place'] ?? null,
                        'birth_province' => $b['beneficiary_birth_province'] ?? null,
                        'birth_country'  => $b['beneficiary_birth_country'] ?? null,
                    ]);
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
                    'teacher_id'  => $b['teacher_id'] ?? null,

                    'meet_url' => $b['meet_url'] ?? null,
                    'notes'    => $b['notes'] ?? null,
                ]);
            }

            // 2) Pagamenti (con error handling)
            try {
                Installment::where('contract_id', $contract->id)->delete();

                $coursePrice   = (float) $contract->course_price;
                $enrollmentFee = (float) $contract->enrollment_fee;
                $deposit       = (float) $contract->deposit;

                $total = $coursePrice + $enrollmentFee;

                $baseDate = $contract->admission_date
                    ? Carbon::parse($contract->admission_date)
                    : now();

                $nextNumber = 1;

                // Tassa iscrizione (0)
                if ($enrollmentFee > 0) {
                    Installment::create([
                        'contract_id' => $contract->id,
                        'number'      => 0,
                        'is_deposit'  => false,
                        'due_date'    => $baseDate->toDateString(),
                        'amount'      => round($enrollmentFee, 2),
                        'status'      => 'unpaid',
                    ]);
                }

                // Acconto (1)
                if ($deposit > 0) {
                    Installment::create([
                        'contract_id' => $contract->id,
                        'number'      => $nextNumber,
                        'is_deposit'  => true,
                        'due_date'    => $baseDate->toDateString(),
                        'amount'      => round($deposit, 2),
                        'status'      => 'unpaid',
                    ]);
                    $nextNumber++;
                }

                $residual = max(0, $total - $deposit);
                if ($residual <= 0) {
                    return;
                }

                if (($contract->payment_mode ?? 'single') === 'installments') {
                    $count = max(1, (int) ($contract->installments_count ?? 1));

                    $first = $contract->first_installment_date
                        ? Carbon::parse($contract->first_installment_date)
                        : $baseDate->copy()->addDays(15);

                    $base = floor(($residual / $count) * 100) / 100;
                    $sum  = 0.0;

                    $firstInstallmentNumber = $nextNumber;
                    $lastInstallmentNumber  = $firstInstallmentNumber + ($count - 1);

                    for ($i = 0; $i < $count; $i++) {
                        $sum += $base;

                        Installment::create([
                            'contract_id' => $contract->id,
                            'number'      => $firstInstallmentNumber + $i,
                            'is_deposit'  => false,
                            'due_date'    => $first->copy()->addMonths($i)->toDateString(),
                            'amount'      => $base,
                            'status'      => 'unpaid',
                        ]);
                    }

                    $diff = round($residual - $sum, 2);
                    if ($diff !== 0.0) {
                        $last = Installment::query()
                            ->where('contract_id', $contract->id)
                            ->where('number', $lastInstallmentNumber)
                            ->first();

                        if ($last) {
                            $last->amount = round(((float) $last->amount + $diff), 2);
                            $last->save();
                        }
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

        // 3) Slot (after commit)
        $contractId = (int) $contract->id;

        DB::afterCommit(function () use ($contractId) {
            try {
                $contract = Contract::query()
                    ->with(['beneficiaries'])
                    ->findOrFail($contractId);

                foreach ($contract->beneficiaries as $cs) {
                    if (! $cs->student_id) continue;
                    if (! $cs->weekly_day || ! $cs->weekly_time) continue;

                    ContractLessonSlot::updateOrCreate(
                        [
                            'contract_id' => $contract->id,
                            'student_id'  => (int) $cs->student_id,
                            'weekly_day'  => (int) $cs->weekly_day,
                            'weekly_time' => $cs->weekly_time,
                        ],
                        [
                            'teacher_id'       => $cs->teacher_id,
                            'duration_minutes' => 60,
                            'is_active'        => true,
                            'starts_at'        => $contract->starts_at
                                ? Carbon::parse($contract->starts_at)->toDateString()
                                : null,
                            'ends_at'          => null,
                            'meet_url'         => $cs->meet_url,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                report($e);

                Notification::make()
                    ->title('Contratto salvato, ma slot non creati')
                    ->body('Controlla i dati dello slot e riprova. Se il problema continua, contatta la segreteria.')
                    ->warning()
                    ->send();
            }
        });
    }

    /**
     * ✅ Crea/riusa BillingProfile privato e assegna billing_profile_id
     */
    private function attachOrCreateBillingProfileForPrivate(array $data): array
    {
        if (! empty($data['billing_profile_id'])) {
            return $data;
        }

        $first = trim((string) ($data['billing_first_name'] ?? ''));
        $last  = trim((string) ($data['billing_last_name'] ?? ''));
        $email = Str::lower(trim((string) ($data['billing_email'] ?? '')));
        $cf    = Str::upper(preg_replace('/\s+/', '', (string) ($data['billing_tax_code'] ?? '')));

        // Se non ho dati minimi, non creo nulla
        if ($first === '' && $last === '' && $email === '' && $cf === '') {
            return $data;
        }

        $q = BillingProfile::query()->where('type', 'private');

        // dedup: prima CF, poi email
        if ($cf !== '') {
            $q->whereRaw('UPPER(COALESCE(fiscal_code,"")) = ?', [$cf]);
        } elseif ($email !== '') {
            $q->whereRaw('LOWER(COALESCE(email,"")) = ?', [$email]);
        } else {
            // fallback: nome+cognome (meno affidabile)
            $q->whereRaw('LOWER(COALESCE(first_name,"")) = ?', [Str::lower($first)])
              ->whereRaw('LOWER(COALESCE(last_name,""))  = ?', [Str::lower($last)]);
        }

        $profile = $q->first();

        if (! $profile) {
            $profile = BillingProfile::create([
                'type'       => 'private',
                'first_name' => $first ?: null,
                'last_name'  => $last ?: null,
                'email'      => $email !== '' ? $email : null,
                'phone'      => $data['billing_phone'] ?? null,

                'fiscal_code'=> $cf !== '' ? $cf : null,
                'vat_number' => $data['billing_vat_number'] ?? null,
                'sdi_code'   => $data['billing_sdi'] ?? null,
                'pec'        => $data['billing_pec'] ?? null,

                'address'    => $data['billing_address'] ?? null,
                'city'       => $data['billing_city'] ?? null,
                'zip'        => $data['billing_zip'] ?? null,
                'province'   => $data['billing_province'] ?? null,
                'country'    => $data['billing_country'] ?? null,
            ]);
        } else {
            // aggiorna campi mancanti (soft)
            $dirty = false;

            foreach ([
                'first_name' => $first,
                'last_name'  => $last,
                'email'      => $email,
                'phone'      => (string) ($data['billing_phone'] ?? ''),
                'fiscal_code'=> $cf,
            ] as $k => $v) {
                $v = trim((string) $v);
                if ($v !== '' && empty($profile->{$k})) {
                    $profile->{$k} = $v;
                    $dirty = true;
                }
            }

            if ($dirty) $profile->save();
        }

        $data['billing_profile_id'] = (int) $profile->id;

        return $data;
    }

    private function upsertStudentFromBeneficiary(array $data): ?int
    {
        $email = Str::lower(trim((string) ($data['email'] ?? '')));
        $phone = preg_replace('/\s+/', '', (string) ($data['phone'] ?? ''));

        if ($email !== '') {
            $s = Student::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($s) return (int) $s->id;
        }

        if ($phone !== '') {
            $s = Student::query()
                ->whereRaw("REPLACE(COALESCE(phone,''),' ','') = ?", [$phone])
                ->first();
            if ($s) return (int) $s->id;
        }

        $first = trim((string) ($data['first_name'] ?? ''));
        $last  = trim((string) ($data['last_name'] ?? ''));

        if ($first === '' && $last === '') {
            return null;
        }

        $s = Student::create([
            'first_name'     => $first ?: null,
            'last_name'      => $last ?: null,
            'email'          => $email !== '' ? $email : null,
            'phone'          => $phone !== '' ? $phone : null,
            'birth_date'     => $data['birth_date'] ?? null,
            'birth_place'    => $data['birth_place'] ?? null,
            'birth_province' => $data['birth_province'] ?? null,
            'birth_country'  => $data['birth_country'] ?? null,
        ]);

        return (int) $s->id;
    }
}
