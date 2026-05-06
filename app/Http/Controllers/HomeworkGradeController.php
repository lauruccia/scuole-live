<?php

namespace App\Http\Controllers;

use App\Models\HomeworkSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeworkGradeController extends Controller
{
    public function __invoke(Request $request, HomeworkSubmission $submission): RedirectResponse
    {
        $user = $request->user();

        // Ownership check:
        //  - Superadmin / Amministrazione / Segreteria → possono valutare qualsiasi compito
        //  - Docente → solo i compiti dei propri studenti (homework.teacher_id == user.id)
        $isStaff = $user && (
            (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || $user->hasAnyRole(['superadmin', 'super_admin', 'Amministrazione', 'Segreteria'])
        );

        if (! $isStaff) {
            $submission->loadMissing('homework');
            $teacherId = $submission->homework?->teacher_id;
            abort_unless(
                $user && $teacherId && (int) $teacherId === (int) $user->id,
                403,
                'Non sei autorizzato a valutare questo compito.'
            );
        }

        $request->validate([
            'grade'    => ['required', 'string', 'max:50'],
            'feedback' => ['nullable', 'string', 'max:500'],
        ]);

        $submission->update([
            'grade'            => $request->grade,
            'teacher_feedback' => $request->feedback,
            'status'           => 'graded',
            'graded_at'        => now(),
        ]);

        return back()->with('success', 'Compito valutato.');
    }
}
