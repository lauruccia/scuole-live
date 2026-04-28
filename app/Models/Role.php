<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    // lascia vuoto: eredita tutto da Spatie

    protected $casts = [
    'permissions_report' => 'array',
    'permissions_studenti' => 'array',
    'permissions_didattica' => 'array',
    'permissions_risorse' => 'array',
    'permissions_impostazioni' => 'array',
    'permissions_widget' => 'array',
];
}
