<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SchoolPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // DIDATTICA
            'didattica.corsi',
            'didattica.lezioni',
            'didattica.calendario_lezioni',
            'didattica.giorni_chiusura',

            // STUDENTI
            'studenti.studenti',
            'studenti.moduli_iscrizione',
            'studenti.scadenze_pagamenti',

            // RISORSE UMANE
            'risorse_umane.docenti',

            // REPORT
            'report.lezioni_studenti',
            'report.lezioni_docenti',

            // AMMINISTRAZIONE
            'admin.utenti',
            'admin.ruoli',
            'admin.permessi',
        ];

        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }
    }
}
