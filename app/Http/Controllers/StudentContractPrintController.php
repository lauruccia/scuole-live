<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Http\Request;

class StudentContractPrintController extends Controller
{
    public function __invoke(Request $request, Contract $contract)
    {
        $user = $request->user();

        abort_unless($user, 403);

        $studentIds = $user->students()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        abort_unless(! empty($studentIds), 403);

        $belongsToStudent = $contract->students()
            ->whereIn('students.id', $studentIds)
            ->exists();

        abort_unless($belongsToStudent, 403);

        return view('contracts.print', [
            'contract' => $contract->load(['course', 'students', 'installments']),
        ]);
    }
}