<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Assicura che tutti i corsi già caricati siano visibili nel catalogo.
        // La migration precedente li creava con is_public = false (default errato).
        if (Schema::hasColumn('courses', 'is_public')) {
            DB::table('courses')->update([
                'is_active' => true,
                'is_public' => true,
            ]);
        }
    }

    public function down(): void
    {
        // Non si torna indietro su questo
    }
};
