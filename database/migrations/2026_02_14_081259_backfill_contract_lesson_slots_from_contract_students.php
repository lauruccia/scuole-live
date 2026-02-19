<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Inserisce slot solo se non esiste già uno slot uguale
        DB::table('contract_students')
            ->whereNotNull('weekly_day')
            ->whereNotNull('weekly_time')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $cs) {

                    $exists = DB::table('contract_lesson_slots')
                        ->where('contract_id', $cs->contract_id)
                        ->where('student_id', $cs->student_id)
                        ->where('teacher_id', $cs->teacher_id)
                        ->where('weekly_day', $cs->weekly_day)
                        ->where('weekly_time', $cs->weekly_time)
                        ->exists();

                    if ($exists) continue;

                    DB::table('contract_lesson_slots')->insert([
                        'contract_id'       => $cs->contract_id,
                        'student_id'        => $cs->student_id,
                        'teacher_id'        => $cs->teacher_id,
                        'weekly_day'        => $cs->weekly_day,
                        'weekly_time'       => $cs->weekly_time,
                        'duration_minutes'  => 60,
                        'is_active'         => 1,
                        'starts_at'         => null,
                        'ends_at'           => null,
                        'meet_url'          => $cs->meet_url,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // rollback “soft”: non cancelliamo tutto, è solo un backfill
    }
};
