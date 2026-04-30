<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();

            // Collegamento al contratto (materiale specifico per studente/corso)
            $table->foreignId('contract_id')
                  ->nullable()
                  ->constrained('contracts')
                  ->nullOnDelete();

            // Chi ha caricato il file
            $table->foreignId('uploaded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Lingua/materia (es. 'Inglese', 'Spagnolo')
            $table->string('language')->nullable();

            // Titolo leggibile del materiale
            $table->string('title');

            // Descrizione opzionale
            $table->text('description')->nullable();

            // Percorso del file su storage (disk: local o s3)
            $table->string('file_path');

            // Nome originale del file
            $table->string('file_name');

            // Tipo MIME (es. application/pdf, image/png)
            $table->string('file_mime')->nullable();

            // Dimensione in byte
            $table->unsignedBigInteger('file_size')->nullable();

            // Tipo di materiale: handout, esercizio, audio, video, altro
            $table->string('material_type')->default('handout');

            // Visibile allo studente nel suo portale
            $table->boolean('is_visible')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_materials');
    }
};
