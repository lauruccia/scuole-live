<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validatore Codice Fiscale italiano.
 *
 * Controlla:
 *  - lunghezza esattamente 16 caratteri
 *  - formato: 6 lettere + 2 cifre + 1 lettera + 2 cifre + 1 lettera + 3 cifre + 1 lettera
 *  - checksum (carattere di controllo) calcolato secondo la tabella ufficiale
 *    (DM Finanze 23/12/1976 e ss.mm.ii.)
 *
 * Accetta CF di persone fisiche. NON accetta CF "omocodici" già corretti
 * (questi sono comunque rari e vengono notificati con CF aggiornato).
 *
 * Riferimento: https://www.agenziaentrate.gov.it/portale/web/guest/strumenti/codice-fiscale
 */
class CodiceFiscale implements ValidationRule
{
    /** @var array<string,int> */
    private const ODD_VALUES = [
        '0' => 1, '1' => 0, '2' => 5, '3' => 7, '4' => 9, '5' => 13, '6' => 15,
        '7' => 17, '8' => 19, '9' => 21,
        'A' => 1, 'B' => 0, 'C' => 5, 'D' => 7, 'E' => 9, 'F' => 13, 'G' => 15,
        'H' => 17, 'I' => 19, 'J' => 21, 'K' => 2, 'L' => 4, 'M' => 18, 'N' => 20,
        'O' => 11, 'P' => 3, 'Q' => 6, 'R' => 8, 'S' => 12, 'T' => 14, 'U' => 16,
        'V' => 10, 'W' => 22, 'X' => 25, 'Y' => 24, 'Z' => 23,
    ];

    /** @var array<string,int> */
    private const EVEN_VALUES = [
        '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6,
        '7' => 7, '8' => 8, '9' => 9,
        'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6,
        'H' => 7, 'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13,
        'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20,
        'V' => 21, 'W' => 22, 'X' => 23, 'Y' => 24, 'Z' => 25,
    ];

    private const CHECK_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            // empty è ok per "nullable"; usare ->required() se obbligatorio
            return;
        }

        $cf = strtoupper(preg_replace('/\s+/', '', $value));

        if (strlen($cf) !== 16) {
            $fail('Il :attribute deve essere lungo esattamente 16 caratteri.');
            return;
        }

        if (! preg_match('/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/', $cf)) {
            $fail('Il :attribute non rispetta il formato del Codice Fiscale.');
            return;
        }

        // Calcolo checksum
        $sum = 0;
        for ($i = 0; $i < 15; $i++) {
            $ch = $cf[$i];
            // Posizione 1-based: dispari = 1,3,5...; pari = 2,4,6...
            if (($i + 1) % 2 === 1) {
                $sum += self::ODD_VALUES[$ch] ?? 0;
            } else {
                $sum += self::EVEN_VALUES[$ch] ?? 0;
            }
        }

        $expected = self::CHECK_CHARS[$sum % 26];
        if ($cf[15] !== $expected) {
            $fail('Il :attribute non è valido (carattere di controllo errato).');
        }
    }
}
