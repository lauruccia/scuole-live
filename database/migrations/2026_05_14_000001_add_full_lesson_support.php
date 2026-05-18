<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge il supporto ai segnaposto FULL nei contratti MIX.
 *
 * - lessons.is_full_lesson  → distingue le lezioni FULL dai segnaposto personalizzati
 * - lessons.starts_at       → diventa nullable (segnaposto "da definire" non hanno data)
 * - contract_students.assigned_hours_full → ore FULL assegnate a questo beneficiario
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Aggiungi is_full_lesson a lessons
        Schema::table('lessons', function (Blueprint $table) {
            $table->boolean('is_full_lesson')
                ->default(false)
                ->after('language_id')
                ->comment('true = segnaposto lezione FULL (senza data/ora/docente finché non pianificata).');
        });

        // 2. Rendi starts_at nullable (i segnaposto FULL non hanno ancora data)
        Schema::table('lessons', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable()->change();
        });

        // 3. Aggiungi assigned_hours_full a contract_students
        Schema::table('contract_students', function (Blueprint $table) {
            $table->decimal('assigned_hours_full', 8, 2)
                ->nullable()
                ->default(null)
                ->after('assigned_hours')
                ->comment('Ore FULL assegnate a questo beneficiario (contratti MIX). Ogni lezione FULL = 1h.');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('is_full_lesson');
            $table->dateTime('starts_at')->nullable(false)->change();
        });

        Schema::table('contract_students', function (Blueprint $table) {
            $table->dropColumn('assigned_hours_full');
        });
    }
};
