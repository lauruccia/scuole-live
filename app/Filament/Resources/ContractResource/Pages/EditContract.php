<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\BillingProfile;
use App\Models\Contract;
use App\Models\Installment;
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

    /**
     * ✅ FIX: in edit, se PRIVATO e billing_profile_id vuoto ma billing_* compilati,
     * crea/riusa BillingProfile e assegna billing_profile_id.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // normalizzo email
        if (! empty($data['billing_email'])) {
            $data['billing_email'] = Str::lower(trim((string) $data['billing_email']));
        }
        if (! empty($data['pec'])) {
            $data['pec'] = Str::lower(trim((string) $data['pec']));
        }
        if (! empty($data['billing_pec'])) {
            $data['billing_pec'] = Str::lower(trim((string) $data['billing_pec']));
        }

        // compat flags
        $data['billing_is_student'] = (int) ($data['billing_is_student'] ?? ($data['billing_is_beneficiary'] ?? 0));
        unset($data['billing_is_beneficiary']);

        if (($data['billing_type'] ?? 'private') === 'company') {
            $data['billing_is_student'] = 0;
        }

        if (($data['billing_type'] ?? 'private') === 'private') {
            $data = $this->attachOrCreateBillingProfileForPrivate($data);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_lessons_safe')
                ->label('Genera / completa lezioni')
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
                        Notification::make()->title('Lezioni generate correttamente')->success()->send();
                    }
                }),

            Actions\Action::make('regenerate_lessons_future')
                ->label('Rigenera lezioni (cancella future)')
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
                        Notification::make()->title('Lezioni rigenerate correttamente')->success()->send();
                    }
                }),

            Actions\Action::make('regenerate_installments')
                ->label('Rigenera scadenze e pagamenti')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->visible(fn (): bool => $this->canManagePayments())
                ->requiresConfirmation()
                ->modalHeading('Rigenera scadenze e pagamenti')
                ->modalDescription('Ricrea: Tassa iscrizione, Acconto, e Saldo/Rate in base ai campi del contratto.')
                ->action(function (): void {
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
                }),

            Actions\DeleteAction::make()
                ->label('Elimina')
                ->color('danger')
                ->visible(fn (): bool => $this->canManageLessons()),
        ];
    }

    private function attachOrCreateBillingProfileForPrivate(array $data): array
    {
        if (! empty($data['billing_profile_id'])) {
            return $data;
        }

        $first = trim((string) ($data['billing_first_name'] ?? ''));
        $last  = trim((string) ($data['billing_last_name'] ?? ''));
        $email = Str::lower(trim((string) ($data['billing_email'] ?? '')));
        $cf    = Str::upper(preg_replace('/\s+/', '', (string) ($data['billing_tax_code'] ?? '')));

        if ($first === '' && $last === '' && $email === '' && $cf === '') {
            return $data;
        }

        $q = BillingProfile::query()->where('type', 'private');

        if ($cf !== '') {
            $q->whereRaw('UPPER(COALESCE(fiscal_code,"")) = ?', [$cf]);
        } elseif ($email !== '') {
            $q->whereRaw('LOWER(COALESCE(email,"")) = ?', [$email]);
        } else {
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
        }

        $data['billing_profile_id'] = (int) $profile->id;

        return $data;
    }

    private function canManageLessons(): bool
    {
        $u = Auth::user();

        return (bool) ($u?->hasAnyRole([
            'superadmin', 'amministrazione', 'segreteria',
            'Amministrazione', 'Segreteria',
        ]) ?? false);
    }

    private function canManagePayments(): bool
    {
        return $this->canManageLessons();
    }

    private function rebuildInstallments(Contract $contract): int
    {
        Installment::where('contract_id', $contract->id)->delete();

        $coursePrice   = (float) $contract->course_price;
        $enrollmentFee = (float) $contract->enrollment_fee;
        $deposit       = (float) $contract->deposit;

        $total = $coursePrice + $enrollmentFee;

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

        $residual = max(0, $total - $deposit);
        if ($residual <= 0) {
            return $created;
        }

        if (($contract->payment_mode ?? 'single') === 'installments') {
            $count = max(1, (int) ($contract->installments_count ?? 1));

            $first = $contract->first_installment_date
                ? Carbon::parse($contract->first_installment_date)
                : $baseDate->copy()->addDays(15);

            $base = floor(($residual / $count) * 100) / 100;
            $sum  = 0.0;

            for ($i = 1; $i <= $count; $i++) {
                $sum += $base;

                Installment::create([
                    'contract_id' => $contract->id,
                    'number'      => $i,
                    'is_deposit'  => false,
                    'due_date'    => $first->copy()->addMonths($i - 1)->toDateString(),
                    'amount'      => $base,
                    'status'      => 'unpaid',
                ]);

                $created++;
            }

            $diff = round($residual - $sum, 2);
            if ($diff !== 0.0) {
                $last = Installment::query()
                    ->where('contract_id', $contract->id)
                    ->where('number', $count)
                    ->first();

                if ($last) {
                    $last->amount = round(((float) $last->amount + $diff), 2);
                    $last->save();
                }
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
                ->title('Errore durante l’operazione')
                ->body('Operazione non completata. Riprova tra qualche secondo o contatta la segreteria.')
                ->danger()
                ->send();

            return 0;
        } finally {
            $lock->release();
        }
    }
}
