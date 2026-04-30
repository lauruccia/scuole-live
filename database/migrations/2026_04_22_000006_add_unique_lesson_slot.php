<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Controlla se l'indice esiste già prima di crearlo
        $indexes = collect(\DB::select("SHOW INDEX FROM lessons WHERE Key_name = 'uniq_lesson_slot'"));
        if ($indexes->isEmpty()) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->unique(['contract_student_id', 'starts_at'], 'uniq_lesson_slot');
            });
        }
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropUnique('uniq_lesson_slot');
        });
    }
};
