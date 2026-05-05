<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validatore Partita IVA italiana (11 cifre) con algoritmo di Luhn modificato.
 *
 * Controllo:
 *  - 11 cifre esatte
 *  - checksum mod-10 secondo Decreto MEF (algoritmo "Luhn-like" italiano)
 *
 * Riferimento: art. 13 DPR 633/72 e DM 23/12/1976.
 */
class PartitaIva implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $piva = preg_replace('/\s+/', '', $value);

        if (! preg_match('/^[0-9]{11}$/', $piva)) {
            $fail('Il :attribute deve essere composto da 11 cifre.');
            return;
        }

        // Algoritmo MEF: somma dei dispari + somma "raddoppiata" dei pari (con riduzione > 9)
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $digit = (int) $piva[$i];
            if (($i + 1) % 2 === 1) {
                // posizione dispari (1, 3, 5...)
                $sum += $digit;
            } else {
                // posizione pari (2, 4, 6...) — raddoppia, se > 9 sottrai 9
                $double = $digit * 2;
                $sum   += ($double > 9) ? $double - 9 : $double;
            }
        }

        if ($sum % 10 !== 0) {
            $fail('Il :attribute non è valido (carattere di controllo errato).');
        }
    }
}
