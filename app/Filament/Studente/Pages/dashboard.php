<?php

namespace App\Filament\Studente\Pages;

use Filament\Pages\Dashboard as BaseDashboard;


class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Area Studente';
    protected static bool $shouldRegisterNavigation = false;
}