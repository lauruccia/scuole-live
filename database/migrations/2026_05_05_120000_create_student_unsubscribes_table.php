<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabella per le disiscrizioni email GDPR-compliant.
 *
 * Idea:
 *  - Ogni studente che clicca il link "disiscrivi" nel footer email finisce qui.
 *  - InvioComunicazioni filtra gli email presenti in questa tabella prima dell'invio.
 *  - La segreteria può "riabilitare" uno studente cancellando la sua riga
 *    (DeleteAction nel Filament resource).
 *
 * Si normalizza l'email in lowercase (case-insensitive matching).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_unsubscribes', function (Blueprint $table) {
            $table->id();
            $table->string('email', 200)->unique();
            $table->string('reason', 100)->nullable();   // motivo opzionale
            $table->ipAddress('ip_address')->nullable();  // tracciabilità
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('unsubscribed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_unsubscribes');
    }
};
