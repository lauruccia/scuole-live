<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge soft delete su course_purchases per audit trail dei pagamenti online.
 * Un acquisto cancellato (es. per rimborso o errore) non viene più eliminato
 * fisicamente ma rimane tracciato con deleted_at valorizzato.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_purchases', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('course_purchases', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
