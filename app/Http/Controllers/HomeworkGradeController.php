<?php

namespace App\Http\Controllers;

use App\Models\HomeworkSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeworkGradeController extends Controller
{
    public function __invoke(Request $request, HomeworkSubmission $submission): RedirectResponse
    {
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
