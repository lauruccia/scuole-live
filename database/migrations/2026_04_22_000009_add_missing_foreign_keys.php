<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge le foreign key mancanti che erano state commentate nelle migrazioni
 * originali di contract_students e lessons.
 *
 * - contract_students.student_id  → students.id  (nullOnDelete)
 * - contract_students.teacher_id  → users.id     (nullOnDelete)
 * - lessons.contract_student_id   → contract_students.id (nullOnDelete)
 * - lessons.student_id            → students.id  (nullOnDelete)
 * - lessons.teacher_id            → users.id     (nullOnDelete)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // PULIZIA ORFANI: azzera i valori che punterebbero a righe inesistenti
        // per evitare errori di integrità referenziale al momento dell'aggiunta
        // delle foreign key.
        // ------------------------------------------------------------------

        // lessons.contract_student_id → contract_students.id
        \DB::statement("
            UPDATE lessons
            SET contract_student_id = NULL
            WHERE contract_student_id IS NOT NULL
              AND contract_student_id NOT IN (SELECT id FROM contract_students)
        ");

        // lessons.student_id → students.id
        \DB::statement("
            UPDATE lessons
            SET student_id = NULL
            WHERE student_id IS NOT NULL
              AND student_id NOT IN (SELECT id FROM students)
        ");

        // lessons.teacher_id → users.id
        \DB::statement("
            UPDATE lessons
            SET teacher_id = NULL
            WHERE teacher_id IS NOT NULL
              AND teacher_id NOT IN (SELECT id FROM users)
        ");

        // contract_students.student_id → students.id
        \DB::statement("
            UPDATE contract_students
            SET student_id = NULL
            WHERE student_id IS NOT NULL
              AND student_id NOT IN (SELECT id FROM students)
        ");

        // contract_students.teacher_id → users.id
        \DB::statement("
            UPDATE contract_students
            SET teacher_id = NULL
            WHERE teacher_id IS NOT NULL
              AND teacher_id NOT IN (SELECT id FROM users)
        ");

        // ------------------------------------------------------------------

        // --- contract_students ---
        Schema::table('contract_students', function (Blueprint $table) {
            // FK student_id
            $existingFks = collect(\DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_students'
                 AND COLUMN_NAME = 'student_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
            ));
            if ($existingFks->isEmpty()) {
                $table->foreign('student_id')
                      ->references('id')->on('students')
                      ->nullOnDelete();
            }

            // FK teacher_id
            $existingFks = collect(\DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contract_students'
                 AND COLUMN_NAME = 'teacher_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
            ));
            if ($existingFks->isEmpty()) {
                $table->foreign('teacher_id')
                      ->references('id')->on('users')
                      ->nullOnDelete();
            }
        });

        // --- lessons ---
        Schema::table('lessons', function (Blueprint $table) {
            // FK contract_student_id
            $existingFks = collect(\DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lessons'
                 AND COLUMN_NAME = 'contract_student_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
            ));
            if ($existingFks->isEmpty()) {
                $table->foreign('contract_student_id')
                      ->references('id')->on('contract_students')
                      ->nullOnDelete();
            }

            // FK student_id
            $existingFks = collect(\DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lessons'
                 AND COLUMN_NAME = 'student_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
            ));
            if ($existingFks->isEmpty()) {
                $table->foreign('student_id')
                      ->references('id')->on('students')
                      ->nullOnDelete();
            }

            // FK teacher_id
            $existingFks = collect(\DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lessons'
                 AND COLUMN_NAME = 'teacher_id' AND REFERENCED_TABLE_NAME IS NOT NULL"
            ));
            if ($existingFks->isEmpty()) {
                $table->foreign('teacher_id')
                      ->references('id')->on('users')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropForeign(['student_id']);
            $table->dropForeign(['contract_student_id']);
        });

        Schema::table('contract_students', function (Blueprint $table) {
            $table->dropForeign(['teacher_id']);
            $table->dropForeign(['student_id']);
        });
    }
};
