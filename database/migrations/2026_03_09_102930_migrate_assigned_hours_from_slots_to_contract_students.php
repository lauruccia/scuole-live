<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('contract_students') ||
            ! Schema::hasTable('contract_lesson_slots') ||
            ! Schema::hasColumn('contract_students', 'assigned_hours') ||
            ! Schema::hasColumn('contract_lesson_slots', 'assigned_hours')
        ) {
            return;
        }

        $rows = DB::table('contract_lesson_slots')
            ->select(
                'contract_id',
                'student_id',
                DB::raw('MAX(assigned_hours) as assigned_hours')
            )
            ->whereNotNull('student_id')
            ->whereNotNull('assigned_hours')
            ->groupBy('contract_id', 'student_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('contract_students')
                ->where('contract_id', $row->contract_id)
                ->where('student_id', $row->student_id)
                ->update([
                    'assigned_hours' => (int) $row->assigned_hours,
                ]);
        }
    }

    public function down(): void
    {
        // Ripristina assigned_hours in contract_students a NULL.
        // I valori originali in contract_lesson_slots sono ancora presenti
        // (la colonna viene eliminata da una migrazione separata).
        if (Schema::hasTable('contract_students') && Schema::hasColumn('contract_students', 'assigned_hours')) {
            DB::table('contract_students')->update(['assigned_hours' => null]);
        }
    }
};
