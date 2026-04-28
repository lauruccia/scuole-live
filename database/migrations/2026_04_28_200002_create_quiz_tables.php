<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Domande del quiz
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();

            // Lingua del quiz (Inglese, Spagnolo, ecc.)
            $table->string('language');

            // Testo della domanda
            $table->text('question_text');

            // Le 4 opzioni di risposta (JSON)
            // es. ["A dog", "A cat", "A bird", "A fish"]
            $table->json('options');

            // Indice (0-based) della risposta corretta
            $table->unsignedTinyInteger('correct_index');

            // Livello CEFR a cui appartiene la domanda (A1, A2, B1, B2, C1, C2)
            $table->string('cefr_level')->default('A1');

            // Ordine di visualizzazione
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Attiva/disattiva la domanda
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // Tentativi di quiz (ogni volta che qualcuno fa il test)
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();

            // Lingua testata
            $table->string('language');

            // Utente registrato (null se è un visitatore pubblico)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Lead (se il quiz viene fatto dal form pubblico)
            $table->foreignId('lead_id')
                  ->nullable()
                  ->constrained('leads')
                  ->nullOnDelete();

            // Risposte date (JSON): [{"question_id": 1, "given_index": 2}, ...]
            $table->json('answers');

            // Punteggio totale (numero risposte corrette)
            $table->unsignedSmallInteger('score')->default(0);

            // Numero totale di domande presentate
            $table->unsignedSmallInteger('total_questions')->default(0);

            // Livello CEFR risultante (calcolato alla fine)
            $table->string('result_level')->nullable();

            // IP/info per tracciare tentativi anonimi
            $table->string('ip_address')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
    }
};
