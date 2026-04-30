<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Compiti assegnati dal docente
        Schema::create('homeworks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_id')
                  ->constrained('contracts')
                  ->cascadeOnDelete();

            $table->foreignId('teacher_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Titolo del compito (es. "Esercizi pagina 42-44")
            $table->string('title');

            // Istruzioni dettagliate
            $table->text('instructions')->nullable();

            // Eventuale file allegato dal docente (testo, traccia...)
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();

            // Scadenza consegna
            $table->dateTime('due_at')->nullable();

            // Lingua/materia
            $table->string('language')->nullable();

            $table->timestamps();
        });

        // Consegne degli studenti
        Schema::create('homework_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('homework_id')
                  ->constrained('homeworks')
                  ->cascadeOnDelete();

            // Lo studente che consegna (tramite user_id)
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->cascadeOnDelete();

            // File consegnato dallo studente
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_mime')->nullable();

            // Nota testuale opzionale dello studente
            $table->text('student_note')->nullable();

            // Voto (stringa: "8/10", "B+", "Ottimo", ecc.)
            $table->string('grade')->nullable();

            // Commento del docente
            $table->text('teacher_feedback')->nullable();

            // Stato: pending | submitted | graded
            $table->string('status')->default('pending');

            // Quando lo studente ha consegnato
            $table->dateTime('submitted_at')->nullable();

            // Quando il docente ha valutato
            $table->dateTime('graded_at')->nullable();

            $table->timestamps();

            $table->unique(['homework_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homework_submissions');
        Schema::dropIfExists('homeworks');
    }
};
