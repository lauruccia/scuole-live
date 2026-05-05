<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge la foreign key contracts.course_id → courses.id.
 * Nella migration originale (2026_02_04_161637) era commentata perché la tabella
 * courses non esisteva ancora al momento della creazione. Ora che esiste,
 * la FK viene aggiunta qui in modo sicuro con nullOnDelete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Prima assicura che eventuali course_id orfani vengano nullati
            // (evita errore di FK constraint su dati già esistenti)
            \Illuminate\Support\Facades\DB::statement(
                'UPDATE contracts SET course_id = NULL WHERE course_id IS NOT NULL AND course_id NOT IN (SELECT id FROM courses)'
            );

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
        });
    }
};
