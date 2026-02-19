<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class ReportLinks extends Widget
{
    protected static string $view = 'filament.widgets.report-links';

    public static function canView(): bool
    {
        $u = auth()->user();

        return $u?->hasAnyRole(['superadmin', 'amministrazione', 'segreteria']) ?? false;
    }
}
