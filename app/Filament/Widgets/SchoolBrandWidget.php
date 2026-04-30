<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SchoolBrandWidget extends Widget
{
        protected static bool $isDiscovered = false;

    protected static string $view = 'filament.widgets.school-brand-widget';

    // tutta la riga (2 card) come in foto
    protected int|string|array $columnSpan = 'full';

    // opzionale: mettilo in alto
    protected static ?int $sort = -100;
}
