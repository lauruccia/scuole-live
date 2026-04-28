<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge indici sulle colonne usate più frequentemente in WHERE / JOIN,
 * per evitare full table scan che rallentano linearmente con la crescita dei dati.
 */
return new class extends Migration
{
    public function up(): void
    {
        // lessons.contract_id — usato in quasi tutti i where/join delle lezioni
        Schema::table('lessons', function (Blueprint $table) {
            if (! $this->indexExists('lessons', 'lessons_contract_id_index')) {
                $table->index('contract_id', 'lessons_contract_id_index');
            }
        });

        // contract_students.student_id — usato nel sync beneficiari e nelle query studenti
        Schema::table('contract_students', function (Blueprint $table) {
            if (! $this->indexExists('contract_students', 'contract_students_student_id_index')) {
                $table->index('student_id', 'contract_students_student_id_index');
            }
        });

        // contract_lesson_slots: indice composito usato in updateOrCreate
        Schema::table('contract_lesson_slots', function (Blueprint $table) {
            if (! $this->indexExists('contract_lesson_slots', 'cls_contract_student_day_time_idx')) {
                $table->index(
                    ['contract_id', 'student_id', 'weekly_day', 'weekly_time'],
                    'cls_contract_student_day_time_idx'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('contract_lesson_slots', function (Blueprint $table) {
            $table->dropIndex('cls_contract_student_day_time_idx');
        });

        Schema::table('contract_students', function (Blueprint $table) {
            $table->dropIndex('contract_students_student_id_index');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex('lessons_contract_id_index');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = collect(\DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'"));
        return $indexes->isNotEmpty();
    }
};
