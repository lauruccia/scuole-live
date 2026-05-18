<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La colonna installments.number era UNSIGNED INT e non accettava -1,
 * valore usato per identificare la tassa di iscrizione (rata n. -1).
 * La rendiamo INT signed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->integer('number')->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->unsignedInteger('number')->default(1)->change();
        });
    }
};
