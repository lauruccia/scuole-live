<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validatore IBAN secondo ISO 13616 con verifica mod-97.
 *
 * Controllo:
 *  - lunghezza fra 15 e 34 caratteri (limite ISO)
 *  - struttura: 2 lettere paese + 2 cifre check + BBAN alfanumerico
 *  - checksum mod-97: spostando i primi 4 caratteri in fondo, convertendo le
 *    lettere (A=10, B=11, ..., Z=35) e calcolando il modulo 97 deve dare 1.
 *
 * Note: per IT lunghezza obbligatoria 27 caratteri; il check generale copre tutti i paesi.
 */
class Iban implements ValidationRule
{
    /** Lunghezze IBAN per paese (subset principali) */
    private const COUNTRY_LENGTHS = [
        'AD' => 24, 'AE' => 23, 'AT' => 20, 'BE' => 16, 'BG' => 22, 'CH' => 21,
        'CY' => 28, 'CZ' => 24, 'DE' => 22, 'DK' => 18, 'EE' => 20, 'ES' => 24,
        'FI' => 18, 'FR' => 27, 'GB' => 22, 'GR' => 27, 'HR' => 21, 'HU' => 28,
        'IE' => 22, 'IS' => 26, 'IT' => 27, 'LI' => 21, 'LT' => 20, 'LU' => 20,
        'LV' => 21, 'MC' => 27, 'MT' => 31, 'NL' => 18, 'NO' => 15, 'PL' => 28,
        'PT' => 25, 'RO' => 24, 'SE' => 24, 'SI' => 19, 'SK' => 24, 'SM' => 27,
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        // Rimuovi spazi, normalizza maiuscolo
        $iban = strtoupper(preg_replace('/\s+/', '', $value));

        if (strlen($iban) < 15 || strlen($iban) > 34) {
            $fail('L\'IBAN deve avere fra 15 e 34 caratteri.');
            return;
        }

        if (! preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
            $fail('L\'IBAN ha un formato non valido.');
            return;
        }

        $country = substr($iban, 0, 2);
        $expectedLen = self::COUNTRY_LENGTHS[$country] ?? null;
        if ($expectedLen !== null && strlen($iban) !== $expectedLen) {
            $fail("L'IBAN per il paese {$country} deve avere {$expectedLen} caratteri.");
            return;
        }

        // Calcolo mod-97: sposta i primi 4 caratteri in fondo, sostituisci lettere con A=10..Z=35
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $numeric    = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $ch = $rearranged[$i];
            if (ctype_alpha($ch)) {
                $numeric .= (string)(ord($ch) - ord('A') + 10);
            } else {
                $numeric .= $ch;
            }
        }

        // Calcolo modulo per stringa numerica grande senza bcmath
        $remainder = 0;
        for ($i = 0; $i < strlen($numeric); $i++) {
            $remainder = ($remainder * 10 + (int)$numeric[$i]) % 97;
        }

        if ($remainder !== 1) {
            $fail('L\'IBAN non è valido (checksum errato).');
        }
    }
}
