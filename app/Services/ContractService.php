<?php

namespace App\Services;

use App\Models\BillingProfile;
use App\Models\Contract;
use Illuminate\Support\Str;

/**
 * Metodi condivisi tra CreateContract e EditContract.
 */
class ContractService
{
    /**
     * Risolve le ore PERSONALIZZATE del contratto usate per la generazione automatica lezioni.
     *
     * Ore personalizzate = hours_purchased - hours_full
     *
     * Le ore "full immersion" (hours_full) sono escluse dal generatore perché vengono
     * pianificate on-demand dall'amministrazione (più studenti, nessuno slot fisso).
     * Contratti precedenti alla migrazione hanno hours_full = 0/null → comportamento invariato.
     *
     * Fallback: usa il corso collegato se il contratto non ha hours_purchased esplicito.
     */
    public function resolveContractHoursTotal(Contract $contract): float
    {
        $total = data_get($contract, 'hours_purchased');
        if ($total === null || ! is_numeric($total) || (float) $total <= 0) {
            $total = data_get($contract, 'course.hours_purchased');
        }

        $total = (float) ($total ?? 0);
        if ($total <= 0) {
            return 0.0;
        }

        $full = (float) (data_get($contract, 'hours_full') ?? 0);

        return max(0.0, $total - $full);
    }

    /**
     * Ore full immersion del contratto (pianificate on-demand, non auto-generate).
     */
    public function resolveContractFullHours(Contract $contract): float
    {
        return max(0.0, (float) (data_get($contract, 'hours_full') ?? 0));
    }

    /**
     * Cerca o crea un BillingProfile per intestatari privati.
     * Se il profilo esiste gia (per CF, email o nome+cognome) lo aggiorna
     * con i campi mancanti; altrimenti ne crea uno nuovo.
     * Restituisce l'array $data arricchito con billing_profile_id.
     *
     * FIX: aggiorna anche billing_birth_date / billing_birth_place
     * quando il profilo e gia agganciato (billing_profile_id presente).
     */
    public function attachOrCreateBillingProfileForPrivate(array $data): array
    {
        // Se il profilo e gia agganciato, non occorre fare altro
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
                ->whereRaw('LOWER(COALESCE(last_name,"")) = ?', [Str::lower($last)]);
        }

        $profile = $q->first();

        if (! $profile) {
            $payload = [
                'type'        => 'private',
                'first_name'  => $first ?: null,
                'last_name'   => $last ?: null,
                'email'       => $email !== '' ? $email : null,
                'phone'       => $data['billing_phone'] ?? null,
                'fiscal_code' => $cf !== '' ? $cf : null,
                'vat_number'  => $data['billing_vat_number'] ?? null,
                'sdi_code'    => $data['billing_sdi'] ?? null,
                'pec'         => $data['billing_pec'] ?? null,
                'address'     => $data['billing_address'] ?? null,
                'city'        => $data['billing_city'] ?? null,
                'zip'         => $data['billing_zip'] ?? null,
                'province'    => $data['billing_province'] ?? null,
                'country'     => $data['billing_country'] ?? null,
            ];

            $profile = BillingProfile::create($payload);
        } else {
            $dirty = false;

            foreach ([
                'first_name'  => $first,
                'last_name'   => $last,
                'email'       => $email,
                'phone'       => (string) ($data['billing_phone'] ?? ''),
                'fiscal_code' => $cf,
            ] as $k => $v) {
                $v = trim((string) $v);

                if ($v !== '' && empty($profile->{$k})) {
                    $profile->{$k} = $v;
                    $dirty = true;
                }
            }

            if ($dirty) {
                $profile->save();
            }
        }

        $data['billing_profile_id'] = (int) $profile->id;

        return $data;
    }
}
