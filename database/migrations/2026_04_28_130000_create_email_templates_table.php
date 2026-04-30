<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();

            // Identificatore univoco del template (es. 'welcome_student')
            $table->string('slug')->unique();

            // Nome leggibile per il pannello admin
            $table->string('name');

            // Gruppo/categoria (es. 'Studenti', 'Lezioni', 'Contratti')
            $table->string('category')->default('Generale');

            // Oggetto dell'email (supporta variabili {{nome}})
            $table->string('subject');

            // Corpo HTML del template (supporta variabili {{nome}})
            $table->longText('body_html');

            // Elenco JSON delle variabili disponibili per questo template
            // es. [{"key":"nome","description":"Nome studente"}]
            $table->json('available_variables')->nullable();

            // Evento che scatta questo template automaticamente
            // null = solo manuale
            $table->string('trigger_event')->nullable();

            // Se attivo o disattivato
            $table->boolean('is_active')->default(true);

            // Note interne per gli admin
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
