<?php

namespace Tests\Unit\Rules;

use App\Rules\CodiceFiscale;
use PHPUnit\Framework\TestCase;

class CodiceFiscaleTest extends TestCase
{
    /** @dataProvider validCodes */
    public function test_validates_correct_codice_fiscale(string $cf): void
    {
        $errors = $this->collectErrors($cf);
        $this->assertEmpty($errors, "CF '{$cf}' dovrebbe essere valido, errori: " . implode('; ', $errors));
    }

    /** @dataProvider invalidCodes */
    public function test_rejects_invalid_codice_fiscale(string $cf): void
    {
        $errors = $this->collectErrors($cf);
        $this->assertNotEmpty($errors, "CF '{$cf}' doveva fallire ma è passato.");
    }

    public function test_empty_value_is_skipped(): void
    {
        $this->assertEmpty($this->collectErrors(''));
        $this->assertEmpty($this->collectErrors('   '));
    }

    public static function validCodes(): array
    {
        return [
            ['RSSMRA80A01H501U'], // Mario Rossi 01/01/1980 Roma
            ['BLLBLG75M15F205Z'], // valid sample
        ];
    }

    public static function invalidCodes(): array
    {
        return [
            ['RSSMRA80A01H501X'],   // checksum errato
            ['NOTAVALIDCFAB1234'],  // formato sbagliato
            ['RSSMRA80A01H501'],    // 15 caratteri
            ['RSSMRA80A01H501UX'],  // 17 caratteri
            ['12345678901234RR'],   // formato sbagliato (numeri all'inizio)
        ];
    }

    private function collectErrors(string $cf): array
    {
        $errors = [];
        $rule   = new CodiceFiscale();
        $rule->validate('codice_fiscale', $cf, function ($msg) use (&$errors) {
            $errors[] = $msg;
        });
        return $errors;
    }
}
