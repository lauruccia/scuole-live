<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['superadmin', 'amministrazione', 'segreteria', 'docente', 'studente'] as $name) {
            Role::findOrCreate($name, 'web');
        }
    }
}
