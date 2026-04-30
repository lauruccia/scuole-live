<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corregge i valori di counts_as_consumed su lezioni future non completate.
 *
 * Il vecchio default era 1 (consumata), ma le lezioni future non ancora avvenute
 * devono avere counts_as_consumed = 0. Il valore viene ricalcolato da recomputeFlags()
 * al prossimo salvataggio, ma questa migrazione lo allinea subito in DB
 * per garantire che il LessonGeneratorService cancelli correttamente
 * le lezioni future auto-generate quando si modificano gli slot.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Azzera counts_as_consumed su lezioni:
        // - con starts_at nel futuro (>= oggi)
        // - non cancellate
        // - non completate
        // - con counts_as_consumed = 1 (vecchio default errato)
        DB::table('lessons')
            ->whereNull('cancelled_at')
            ->whereNull('completed_at')
            ->whereDate('starts_at', '>=', now()->toDateString())
            ->where('counts_as_consumed', 1)
            ->update(['counts_as_consumed' => 0]);
    }

    public function down(): void
    {
        // Il rollback non è sicuro: non sappiamo quali lezioni avevano
        // intenzionalmente counts_as_consumed = 1. Non eseguiamo il ripristino.
    }
};
