<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;

class LessonCalendar extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Didattica';
    protected static ?string $navigationLabel = 'Calendario lezioni';
    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.pages.lesson-calendar';

    public static function canAccess(): bool
    {
        /** @var User|null $u */
        $u = auth()->user();
        if (! $u) return false;

        // ✅ Shield super admin (config: super_admin)
        if ($u->hasRole('super_admin')) return true;

        // ✅ tuo superadmin “storico”
        if ($u->hasRole('superadmin')) return true;

        // ✅ permesso page_LessonCalendar (Shield)
        return $u->can('page_' . class_basename(static::class));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
