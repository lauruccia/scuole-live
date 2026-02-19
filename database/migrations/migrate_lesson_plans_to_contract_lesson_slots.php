<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Copia solo se non esiste già uno slot uguale
        $plans = DB::table('lesson_plans')->get();

        foreach ($plans as $p) {
            // ricava contract_id e student_id da contract_students
            $cs = DB::table('contract_students')
                ->where('id', $p->contract_student_id)
                ->first();

            if (! $cs) continue;

            $exists = DB::table('contract_lesson_slots')
                ->where('contract_id', $cs->contract_id)
                ->where('student_id', $cs->student_id)
                ->where('weekly_day', $p->weekly_day)
                ->where('weekly_time', $p->weekly_time)
                ->whereNull('starts_at')
                ->whereNull('ends_at')
                ->exists();

            if ($exists) continue;

            DB::table('contract_lesson_slots')->insert([
                'contract_id'       => $cs->contract_id,
                'student_id'        => $cs->student_id,
                'teacher_id'        => $p->teacher_id,
                'weekly_day'        => $p->weekly_day,
                'weekly_time'       => $p->weekly_time,
                'duration_minutes'  => $p->duration_minutes ?? 60,
                'is_active'         => (int)($p->is_active ?? 1),
                'starts_at'         => $p->start_date,   // se vuoi usarla come inizio slot
                'ends_at'           => null,
                'meet_url'          => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    public function down(): void
    {
        // non faccio rollback per non rischiare di cancellare slot creati dopo
    }
};
