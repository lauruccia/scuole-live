<?php

namespace Tests\Unit\Rules;

use App\Rules\PartitaIva;
use PHPUnit\Framework\TestCase;

class PartitaIvaTest extends TestCase
{
    /** @dataProvider validPiva */
    public function test_validates_correct_piva(string $piva): void
    {
        $errors = $this->collectErrors($piva);
        $this->assertEmpty($errors, "P.IVA '{$piva}' dovrebbe essere valida, errori: " . implode('; ', $errors));
    }

    /** @dataProvider invalidPiva */
    public function test_rejects_invalid_piva(string $piva): void
    {
        $errors = $this->collectErrors($piva);
        $this->assertNotEmpty($errors, "P.IVA '{$piva}' doveva fallire ma è passata.");
    }

    public static function validPiva(): array
    {
        return [
            ['12345678903'],   // P.IVA test valida (checksum corretto)
            ['00000000000'],   // edge: tutti zero passa il check (mod 10 = 0)
        ];
    }

    public static function invalidPiva(): array
    {
        return [
            ['12345678901'],     // checksum errato
            ['1234567890'],      // 10 cifre
            ['ABCDEFGHIJK'],     // lettere
            ['123456789012'],    // 12 cifre
        ];
    }

    private function collectErrors(string $piva): array
    {
        $errors = [];
        $rule   = new PartitaIva();
        $rule->validate('partita_iva', $piva, function ($msg) use (&$errors) {
            $errors[] = $msg;
        });
        return $errors;
    }
}
