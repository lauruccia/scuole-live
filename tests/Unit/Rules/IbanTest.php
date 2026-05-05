<?php

namespace Tests\Unit\Rules;

use App\Rules\Iban;
use PHPUnit\Framework\TestCase;

class IbanTest extends TestCase
{
    /** @dataProvider validIbans */
    public function test_validates_correct_iban(string $iban): void
    {
        $errors = $this->collectErrors($iban);
        $this->assertEmpty($errors, "IBAN '{$iban}' dovrebbe essere valido, errori: " . implode('; ', $errors));
    }

    /** @dataProvider invalidIbans */
    public function test_rejects_invalid_iban(string $iban): void
    {
        $errors = $this->collectErrors($iban);
        $this->assertNotEmpty($errors, "IBAN '{$iban}' doveva fallire ma è passato.");
    }

    public function test_accepts_iban_with_spaces(): void
    {
        $errors = $this->collectErrors('IT60 X054 2811 1010 0000 0123 456');
        $this->assertEmpty($errors, 'IBAN con spazi deve essere accettato dopo normalizzazione.');
    }

    public static function validIbans(): array
    {
        return [
            ['IT60X0542811101000000123456'],   // IT esempio standard
            ['DE89370400440532013000'],         // DE esempio standard
            ['GB82WEST12345698765432'],         // GB esempio standard
        ];
    }

    public static function invalidIbans(): array
    {
        return [
            ['IT60X0542811101000000123457'],    // checksum errato
            ['XX12'],                            // troppo corto
            ['1234567890ABCDEFG'],               // non parte con 2 lettere
            ['IT60X05428111010000001234'],       // IT troppo corto
        ];
    }

    private function collectErrors(string $iban): array
    {
        $errors = [];
        $rule   = new Iban();
        $rule->validate('iban', $iban, function ($msg) use (&$errors) {
            $errors[] = $msg;
        });
        return $errors;
    }
}
