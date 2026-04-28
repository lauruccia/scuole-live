<?php

namespace App\Console\Commands;

use App\Models\BillingProfile;
use App\Models\Company;
use App\Models\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillBillingProfiles extends Command
{
    protected $signature = 'billing:backfill {--dry-run : Non scrive nulla, stampa solo cosa farebbe}';
    protected $description = 'Crea companies e billing_profiles dai dati esistenti in contracts e collega i riferimenti';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $this->info('Backfill billing profiles' . ($dry ? ' (DRY RUN)' : ''));

        $updatedContracts = 0;
        $createdCompanies = 0;
        $createdProfiles  = 0;

        DB::beginTransaction();

        try {
            Contract::query()
                ->orderBy('id')
                ->chunkById(200, function ($contracts) use ($dry, &$updatedContracts, &$createdCompanies, &$createdProfiles) {
                    foreach ($contracts as $contract) {
                        $billingType = $contract->billing_type ?? 'private';

                        // 1) Azienda: crea/aggancia Company + BillingProfile(type=company)
                        if ($billingType === 'company') {
                            $companyName = trim((string) ($contract->company_name ?? ''));
                            if ($companyName === '') {
                                // se manca il nome azienda, saltiamo: contratto incompleto
                                continue;
                            }

                            $vat = trim((string) ($contract->vat_number ?? ''));
                            $pec = trim((string) ($contract->pec ?? ''));

                            $company = null;

                            // match prioritario: P.IVA -> altrimenti nome+PEC -> altrimenti nome
                            if ($vat !== '') {
                                $company = Company::query()->where('vat_number', $vat)->first();
                            }
                            if (! $company && $pec !== '') {
                                $company = Company::query()
                                    ->where('name', $companyName)
                                    ->where('pec', $pec)
                                    ->first();
                            }
                            if (! $company) {
                                $company = Company::query()
                                    ->where('name', $companyName)
                                    ->first();
                            }

                            if (! $company) {
                                $createdCompanies++;
                                if (! $dry) {
                                    $company = Company::create([
                                        'name'      => $companyName,
                                        'vat_number'=> $vat !== '' ? $vat : null,
                                        'tax_code'  => $contract->company_tax_code ?? null,
                                        'sdi_code'  => $contract->sdi ?? null,
                                        'pec'       => $pec !== '' ? $pec : null,
                                        'email'     => $contract->company_email ?? null,
                                        'phone'     => $contract->company_phone ?? null,
                                        'address'   => $contract->company_address ?? null,
                                        'zip'       => $contract->company_zip ?? null,
                                        'city'      => $contract->company_city ?? null,
                                        'province'  => $contract->company_province ?? null,
                                        'country'   => $contract->company_country ?? 'Italia',
                                    ]);
                                }
                            } else {
                                // aggiorna campi mancanti senza sovrascrivere
                                if (! $dry) {
                                    $dirty = false;

                                    if (empty($company->vat_number) && $vat !== '') { $company->vat_number = $vat; $dirty = true; }
                                    if (empty($company->pec) && $pec !== '') { $company->pec = $pec; $dirty = true; }
                                    if (empty($company->sdi_code) && !empty($contract->sdi)) { $company->sdi_code = $contract->sdi; $dirty = true; }
                                    if (empty($company->email) && !empty($contract->company_email)) { $company->email = $contract->company_email; $dirty = true; }
                                    if (empty($company->phone) && !empty($contract->company_phone)) { $company->phone = $contract->company_phone; $dirty = true; }
                                    if (empty($company->address) && !empty($contract->company_address)) { $company->address = $contract->company_address; $dirty = true; }
                                    if (empty($company->city) && !empty($contract->company_city)) { $company->city = $contract->company_city; $dirty = true; }
                                    if (empty($company->zip) && !empty($contract->company_zip)) { $company->zip = $contract->company_zip; $dirty = true; }
                                    if (empty($company->province) && !empty($contract->company_province)) { $company->province = $contract->company_province; $dirty = true; }
                                    if (empty($company->country) && !empty($contract->company_country)) { $company->country = $contract->company_country; $dirty = true; }

                                    if ($dirty) $company->save();
                                }
                            }

                            // collega company_id sul contratto se nullo
                            if (! $dry && $company && empty($contract->company_id)) {
                                $contract->company_id = $company->id;
                            }

                            // billing profile company (uno per company)
                            if (! $dry && $company && empty($contract->billing_profile_id)) {
                                $profile = BillingProfile::query()
                                    ->where('type', 'company')
                                    ->where('company_id', $company->id)
                                    ->first();

                                if (! $profile) {
                                    $createdProfiles++;
                                    $profile = BillingProfile::create([
                                        'type'      => 'company',
                                        'company_id'=> $company->id,
                                        'vat_number'=> $company->vat_number,
                                        'sdi_code'  => $company->sdi_code,
                                        'pec'       => $company->pec,
                                        'email'     => $company->email,
                                        'phone'     => $company->phone,
                                        'address'   => $company->address,
                                        'zip'       => $company->zip,
                                        'city'      => $company->city,
                                        'province'  => $company->province,
                                        'country'   => $company->country,
                                    ]);
                                }

                                $contract->billing_profile_id = $profile->id;
                            }

                            if (! $dry) {
                                if ($contract->isDirty(['company_id', 'billing_profile_id'])) {
                                    $contract->save();
                                    $updatedContracts++;
                                }
                            }

                            continue;
                        }

                        // 2) Privato: crea/aggancia BillingProfile(type=private)
                        $first = trim((string) ($contract->billing_first_name ?? ''));
                        $last  = trim((string) ($contract->billing_last_name ?? ''));
                        $email = trim((string) ($contract->billing_email ?? ''));
                        $fiscal = trim((string) ($contract->billing_tax_code ?? ''));

                        // Se non abbiamo niente, salta
                        if ($first === '' && $last === '' && $email === '' && $fiscal === '') {
                            continue;
                        }

                        if ($dry) {
                            continue;
                        }

                        if (empty($contract->billing_profile_id)) {
                            $q = BillingProfile::query()->where('type', 'private');

                            // match più robusto: CF, poi email, poi nome+cognome
                            if ($fiscal !== '') {
                                $q->where('fiscal_code', $fiscal);
                            } elseif ($email !== '') {
                                $q->where('email', $email);
                            } else {
                                $q->where('first_name', $first)->where('last_name', $last);
                            }

                            $profile = $q->first();

                            if (! $profile) {
                                $createdProfiles++;
                                $profile = BillingProfile::create([
                                    'type'        => 'private',
                                    'first_name'  => $first !== '' ? $first : null,
                                    'last_name'   => $last !== '' ? $last : null,
                                    'fiscal_code' => $fiscal !== '' ? $fiscal : null,
                                    'vat_number'  => $contract->billing_vat_number ?? null,
                                    'sdi_code'    => $contract->billing_sdi ?? null,
                                    'pec'         => $contract->billing_pec ?? null,
                                    'email'       => $email !== '' ? $email : null,
                                    'phone'       => $contract->billing_phone ?? null,
                                    'address'     => $contract->billing_address ?? null,
                                    'zip'         => $contract->billing_zip ?? null,
                                    'city'        => $contract->billing_city ?? null,
                                    'province'    => $contract->billing_province ?? null,
                                    'country'     => $contract->billing_country ?? 'Italia',
                                ]);
                            }

                            $contract->billing_profile_id = $profile->id;
                            $contract->save();
                            $updatedContracts++;
                        }
                    }
                });

            if ($dry) {
                DB::rollBack();
                $this->warn('DRY RUN: nessuna modifica salvata.');
            } else {
                DB::commit();
                $this->info("OK. Contratti aggiornati: {$updatedContracts}");
                $this->info("Companies create: {$createdCompanies}");
                $this->info("Billing profiles create: {$createdProfiles}");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Errore: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
