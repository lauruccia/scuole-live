<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aggiunge deleted_at (SoftDeletes) a contracts, lessons e students.
     *
     * PERCHÉ: senza soft delete, un record eliminato per errore
     * (click sbagliato, test, operazione di pulizia) è irrecuperabile.
     * Su dati finanziari (contratti, rate, lezioni) è inaccettabile in produzione.
     *
     * L'indice su deleted_at garantisce che le query standard con
     * whereNull('deleted_at') — aggiunte automaticamente da Eloquent —
     * restino veloci anche con tabelle grandi.
     */
    public function up(): void
    {
        // ── contracts ────────────────────────────────────────────────────────
        Schema::table('contracts', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        // ── lessons ──────────────────────────────────────────────────────────
        Schema::table('lessons', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        // ── students ─────────────────────────────────────────────────────────
        Schema::table('students', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
