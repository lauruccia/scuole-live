<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TeacherHoursPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $from      = $request->input('from');
        $to        = $request->input('to');
        $teacherId = $request->input('teacher_id');

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : null;

        $teacherIds = Lesson::query()
            ->whereNotNull('teacher_id')
            ->distinct()
            ->pluck('teacher_id');

        $q = User::query()
            ->whereIn('id', $teacherIds)
            ->when($teacherId, fn ($qq) => $qq->where('id', (int) $teacherId));

        $q->selectRaw("
            users.*,
            COALESCE(
                NULLIF(TRIM(users.name), ''),
                NULLIF(TRIM(CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,''))), ''),
                CONCAT('Docente #', users.id)
            ) AS teacher_label
        ");

        $q->selectSub(
            Lesson::query()
                ->selectRaw("COALESCE(SUM(CASE WHEN (CASE WHEN ends_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, starts_at, ends_at) ELSE COALESCE(NULLIF(duration_minutes,0), 60) END) >= 120 THEN 2 ELSE 1 END), 0)")
                ->whereColumn('teacher_id', 'users.id')
                ->whereNull('cancelled_at')
                ->where('counts_as_consumed', 1)
                ->whereNull('recovery_of_lesson_id')
                ->when($fromDate, fn ($lq) => $lq->where('starts_at', '>=', $fromDate))
                ->when($toDate,   fn ($lq) => $lq->where('starts_at', '<=', $toDate)),
            'worked_hours_period'
        );

        $rows = $q->orderBy('teacher_label')->get();

        $totOre      = $rows->sum(fn ($r) => (int) ($r->worked_hours_period ?? 0));
        $totCompenso = $rows->sum(fn ($r) => ((int) ($r->worked_hours_period ?? 0)) * ((float) ($r->teacher_hourly_rate_gross ?? 0)));

        $pdf = Pdf::loadView('reports.teacher-hours-pdf', [
            'rows'        => $rows,
            'from'        => $from,
            'to'          => $to,
            'totOre'      => $totOre,
            'totCompenso' => $totCompenso,
        ])->setPaper('a4', 'landscape');

        $filename = 'report-docenti-' . ($from ?? 'tutto') . '_' . ($to ?? '') . '.pdf';

        return $pdf->download($filename);
    }
}
