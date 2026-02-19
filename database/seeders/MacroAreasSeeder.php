<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MacroAreasSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            'area_dashboard',
            'area_studenti',
            'area_didattica',
            'area_docenti',
            'area_pagamenti',
            'area_report',
            'area_contratti',
            'area_impostazioni', // opzionale
        ];

        foreach ($areas as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }
}
