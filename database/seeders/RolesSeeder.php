<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['superadmin', 'Amministrazione', 'Segreteria', 'Docente', 'Studente'] as $name) {
            Role::findOrCreate($name, 'web');
        }
    }
}
