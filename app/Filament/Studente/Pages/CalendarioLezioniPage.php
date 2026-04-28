<?php

namespace App\Filament\Studente\Pages;

use App\Filament\Studente\Widgets\StudentLessonCalendarWidget;
use Filament\Pages\Page;

class CalendarioLezioniPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Calendario lezioni';
    protected static ?string $title = 'Calendario lezioni';
    protected static string $view = 'filament.studente.pages.calendario-lezioni-page';

    protected function getHeaderWidgets(): array
    {
        return [
            StudentLessonCalendarWidget::class,
        ];
    }
}