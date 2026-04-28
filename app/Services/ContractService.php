<?php

namespace App\Services;

use App\Models\BillingProfile;
use App\Models\Contract;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Metodi condivisi tra CreateContract e EditContract.
 */
class ContractService
{
    /**
     * Risolve il totale ore del contratto cercando il campo giusto
     * tra i possibili nomi usati storicamente.
     */
    public function resolveContractHoursTotal(Contract $contract): float
    {
        $candidates = [
            data_get($contract, 'hours_total'),
            data_get($contract, 'total_hours'),
            data_get($contract, 'course_hours'),
            data_get($contract, 'hours'),
            data_get($contract, 'duration_hours'),
            data_get($contract, 'package_hours'),

            data_get($contract, 'course.hours_total'),
            data_get($contract, 'course.total_hours'),
            data_get($contract, 'course.course_hours'),
            data_get($contract, 'course.hours'),
            data_get($contract, 'course.duration_hours'),
            data_get($contract, 'course.package_hours'),
        ];

        foreach ($candidates as $value) {
            if ($value !== null && $value !== '' && is_numeric($value) && (float) $value > 0) {
                return (float) $value;
            }
        }

        return 0.0;
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
        // Legge i campi anagrafici opzionali passati dal form
        $birthDate  = ! empty($data['billing_birth_date'])  ? $data['billing_birth_date']  : null;
        $birthPlace = ! empty($data['billing_birth_place']) ? trim((string) $data['billing_birth_place']) : null;

        // Se il profilo e gia agganciato, aggiorna solo birth_date / birth_place mancanti
        if (! empty($data['billing_profile_id'])) {
            $profile = BillingProfile::find((int) $data['billing_profile_id']);

            if ($profile) {
                $dirty = false;

                if ($birthDate && empty($profile->birth_date) && Schema::hasColumn('billing_profiles', 'birth_date')) {
                    $profile->birth_date = $birthDate;
                    $dirty = true;
                }
                if ($birthPlace && empty($profile->birth_place) && Schema::hasColumn('billing_profiles', 'birth_place')) {
                    $profile->birth_place = $birthPlace;
                    $dirty = true;
                }

                if ($dirty) {
                    $profile->save();
                }
            }

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

            // Aggiunge birth_date e birth_place solo se le colonne esistono
            if ($birthDate && Schema::hasColumn('billing_profiles', 'birth_date')) {
                $payload['birth_date'] = $birthDate;
            }
            if ($birthPlace && Schema::hasColumn('billing_profiles', 'birth_place')) {
                $payload['birth_place'] = $birthPlace;
            }

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

            // Aggiorna birth_date / birth_place se mancanti nel profilo esistente
            if ($birthDate && empty($profile->birth_date) && Schema::hasColumn('billing_profiles', 'birth_date')) {
                $profile->birth_date = $birthDate;
                $dirty = true;
            }
            if ($birthPlace && empty($profile->birth_place) && Schema::hasColumn('billing_profiles', 'birth_place')) {
                $profile->birth_place = $birthPlace;
                $dirty = true;
            }

            if ($dirty) {
                $profile->save();
            }
        }

        $data['billing_profile_id'] = (int) $profile->id;

        return $data;
    }
}
