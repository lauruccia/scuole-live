<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge indici di performance alla tabella lessons.
 *
 * Motivazione:
 *   - starts_at: usato in ogni query calendario, widget studente, overlap check
 *   - teacher_id + starts_at: overlap docente in LessonRecoveryService
 *   - student_id + starts_at: query prossima lezione studente
 *   - cancelled_at: whereNull('cancelled_at') su ogni query di overlap
 *
 * Tutti gli indici sono "condizionali" (skip se già esistono) per sicurezza.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Indice su starts_at — query calendario e widget
            if (! $this->indexExists('lessons', 'lessons_starts_at_index')) {
                $table->index(['starts_at'], 'lessons_starts_at_index');
            }

            // Indice composto teacher_id + starts_at — overlap docente
            if (! $this->indexExists('lessons', 'lessons_teacher_id_starts_at_index')) {
                $table->index(['teacher_id', 'starts_at'], 'lessons_teacher_id_starts_at_index');
            }

            // Indice composto student_id + starts_at — prossima lezione studente
            if (! $this->indexExists('lessons', 'lessons_student_id_starts_at_index')) {
                $table->index(['student_id', 'starts_at'], 'lessons_student_id_starts_at_index');
            }

            // Indice su cancelled_at — whereNull('cancelled_at') frequente
            if (! $this->indexExists('lessons', 'lessons_cancelled_at_index')) {
                $table->index(['cancelled_at'], 'lessons_cancelled_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            foreach ([
                'lessons_starts_at_index',
                'lessons_teacher_id_starts_at_index',
                'lessons_student_id_starts_at_index',
                'lessons_cancelled_at_index',
            ] as $index) {
                if ($this->indexExists('lessons', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $indexes = collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]));
        return $indexes->isNotEmpty();
    }
};
