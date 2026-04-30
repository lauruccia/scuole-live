<?php

namespace App\Filament\Widgets;

use App\Models\Lesson;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class LessonsTodayWidget extends Widget
{
    protected static string $view = 'filament.widgets.lessons-today';
    protected static ?int   $sort = 1;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['superadmin', 'Amministrazione', 'Segreteria']) ?? false;
    }

    public function getLessonsToday(): array
    {
        $today = Carbon::today();

        $lessons = Lesson::query()
            ->whereDate('starts_at', $today)
            ->whereNull('cancelled_at')
            ->with(['student', 'teacher', 'contract.course'])
            ->orderBy('starts_at')
            ->get();

        $total      = $lessons->count();
        $completed  = $lessons->where('counts_as_consumed', true)->count();
        $upcoming   = $lessons->where('starts_at', '>=', now())->where('counts_as_consumed', false)->count();
        $inProgress = $total - $completed - $upcoming;

        return [
            'lessons'    => $lessons,
            'total'      => $total,
            'completed'  => $completed,
            'upcoming'   => $upcoming,
            'inProgress' => max(0, $inProgress),
        ];
    }
}
