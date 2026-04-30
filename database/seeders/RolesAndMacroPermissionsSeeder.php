<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndMacroPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'superadmin' => [
                // NON serve assegnare tutto se usi Gate::before
                // ma lo lasciamo vuoto o mettiamo tutto se vuoi
            ],

            'amministrazione' => [
                'area_dashboard',
                'area_studenti',
                'area_didattica',
                'area_docenti',
                'area_pagamenti',
                'area_report',
                'area_contratti',
            ],

            'segreteria' => [
                'area_dashboard',
                'area_studenti',
                'area_didattica',
                'area_pagamenti',
                'area_report',
            ],

            'docente' => [
                'area_dashboard',
                'area_didattica',
                'area_docenti',
            ],

            'studente' => [
                'area_dashboard',
                // se vuoi uno spazio studente in futuro
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            if (! empty($permissions)) {
                $role->syncPermissions($permissions);
            }
        }
    }
}
