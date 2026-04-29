<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Ordine importante:
     *  1. RolesSeeder           — crea i ruoli Spatie (superadmin, Docente, ecc.)
     *  2. SchoolPermissionsSeeder — crea/assegna i permessi di sistema
     *  3. EmailTemplateSeeder   — popola i template email di default
     *  4. SchoolSettingSeeder   — popola brand, contatti, IBAN e impostazioni scuola
     */
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            SchoolPermissionsSeeder::class,
            EmailTemplateSeeder::class,
            SchoolSettingSeeder::class,
        ]);
    }
}
