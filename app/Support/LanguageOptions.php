<?php

namespace App\Support;

final class LanguageOptions
{
    public static function all(): array
    {
        // CHIAVI = valori salvati nel DB (strings come "Inglese", "Arabo", ecc.)
        // VALORI = label mostrata
        return [
            'Arabo' => 'Arabo',
            'Francese' => 'Francese',
            'Inglese' => 'Inglese',
            'Spagnolo' => 'Spagnolo',
            'Tedesco' => 'Tedesco',
            'Italiano per stranieri' => 'Italiano per stranieri',
        ];
    }
}
