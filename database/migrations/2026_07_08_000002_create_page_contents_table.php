<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenuti editabili delle pagine pubbliche del sito (mini-CMS).
 *
 * Ogni riga è un singolo campo di testo/immagine/FAQ di una pagina:
 *   page = slug pagina (es. "home"), key = campo (es. "hero_title").
 * Se una riga non esiste, il sito usa il testo predefinito da
 * config/site_contents.php — quindi la tabella può anche restare vuota.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_contents', function (Blueprint $table) {
            $table->id();
            $table->string('page', 60)->index();
            $table->string('key', 120);
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->unique(['page', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_contents');
    }
};
