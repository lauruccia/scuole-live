<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge la colonna course_interest alla tabella leads se non esiste.
 * Necessario quando la tabella leads è stata creata prima della migrazione completa.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'course_interest')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('course_interest')->nullable()->after('phone');
            });
        }

        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'interest_notes')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->text('interest_notes')->nullable()->after('course_interest');
            });
        }

        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'loss_reason')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->text('loss_reason')->nullable();
            });
        }

        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'assigned_to')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'followup_at')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->date('followup_at')->nullable()->index();
            });
        }

        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'notes')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->text('notes')->nullable();
            });
        }

        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'converted_student_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->foreignId('converted_student_id')->nullable()->constrained('students')->nullOnDelete();
            });
        }

        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'converted_at')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->timestamp('converted_at')->nullable();
            });
        }

        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'deleted_at')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        // Non rimuoviamo dati in rollback
    }
};
