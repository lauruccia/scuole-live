<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profili pubblici insegnanti (pagine /insegnanti e /insegnanti/{slug}).
 *
 * Modulo distinto dalla gestione HR "Docenti" (TeacherResource, basato su
 * User con ruolo Docente, usato per contratti/paghe/materie interne):
 * qui c'è SOLO il contenuto pubblicato sul sito pubblico (bio, foto,
 * certificazioni) — utile anche per insegnanti che il cliente non vuole
 * necessariamente collegare a un account nel gestionale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('language')->nullable();        // es. "Inglese"
            $table->string('qualifications')->nullable();   // titoli di studio
            $table->text('certifications')->nullable();     // esami/certificazioni
            $table->longText('bio');
            $table->string('photo')->nullable();            // path su disk "public"
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};
