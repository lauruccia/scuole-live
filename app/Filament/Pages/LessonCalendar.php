<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class LessonCalendar extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Didattica';
    protected static ?string $navigationLabel = 'Calendario lezioni';
    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.pages.lesson-calendar';

protected static function isTeacherPanel(): bool
{
    $id = Filament::getCurrentPanel()?->getId();
    return is_string($id) && strcasecmp($id, 'Docente') === 0;
}

    public static function canAccess(): bool
    {
        /** @var User|null $u */
        $u = auth()->user();
        if (! $u) return false;

        // ✅ nel panel docente: basta essere Docente
        if (static::isTeacherPanel()) {
            return $u->hasRole('Docente');
        }

        // ✅ admin/superadmin: logica tua
        if ($u->hasAnyRole(['super_admin', 'superadmin', 'Superadmin'])) return true;

        // ✅ permesso page_LessonCalendar (Shield)
        return $u->can('page_' . class_basename(static::class));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
