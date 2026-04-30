<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge soft deletes alla tabella installments per preservare
 * l'audit trail delle rate anche dopo la loro eliminazione.
 * Le rate pagate (paid_at NOT NULL) non vengono mai cancellate fisicamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
