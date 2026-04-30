<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Le ore del corso erano state salvate in lessons_count per errore storico.
     * hours_purchased è il campo corretto (ore acquistate dello studente).
     * Copiamo lessons_count -> hours_purchased dove hours_purchased = 0.
     */
    public function up(): void
    {
        DB::statement('
            UPDATE courses
            SET hours_purchased = lessons_count
            WHERE (hours_purchased IS NULL OR hours_purchased = 0)
              AND lessons_count IS NOT NULL
              AND lessons_count > 0
        ');
    }

    public function down(): void
    {
        // Non ripristinare: il rollback manuale è preferibile in questo caso
    }
};
