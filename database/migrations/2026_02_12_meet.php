<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge i campi Google Meet / Calendar alla tabella lessons.
 *
 * NOTA: questa migration era stata salvata senza estensione .php (2026_02_12_meet)
 * e quindi non veniva mai eseguita da artisan. Rinominata il 2026-05-14.
 *
 * Usa Schema::hasColumn() per idempotenza: la migration 2026_04_22_000004 aggiunge
 * le stesse colonne con lo stesso check, quindi se quella è già stata eseguita
 * questa non fa nulla e non va in errore.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('lessons', 'meet_url')) {
                $table->string('meet_url', 500)->nullable()->after('ends_at');
            }
            if (! Schema::hasColumn('lessons', 'google_event_id')) {
                $table->string('google_event_id', 255)->nullable()->after('meet_url');
            }
            if (! Schema::hasColumn('lessons', 'google_calendar_id')) {
                $table->string('google_calendar_id', 255)->nullable()->after('google_event_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Rimuove solo se esistenti, per sicurezza
            $toDrop = array_filter(
                ['meet_url', 'google_event_id', 'google_calendar_id'],
                fn (string $col) => Schema::hasColumn('lessons', $col)
            );
            if (! empty($toDrop)) {
                $table->dropColumn(array_values($toDrop));
            }
        });
    }
};
