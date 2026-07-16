<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pagine personalizzate (page-builder semplificato per lo staff).
 * ─────────────────────────────────────────────────────────────────────────────
 * A differenza di "Contenuti sito" (page_contents — modifica pagine GIÀ
 * esistenti nel codice) questo modulo permette di CREARE pagine nuove da
 * zero, con slug/URL a scelta, gestite interamente dal pannello Filament
 * (CustomPageResource) e pubblicate su /{slug} (route di fallback, l'ULTIMA
 * registrata in routes/web.php — vedi App\Models\CustomPage::reservedSlugs()
 * per la protezione contro collisioni con route reali del sito).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();

            // Testata (hero)
            $table->string('hero_title')->nullable();
            $table->text('hero_subtitle')->nullable();
            $table->string('hero_image')->nullable();      // path su disk "public"

            // Corpo pagina — blocco libero (RichEditor, con immagini incorporabili)
            $table->longText('body');

            // Invito all'azione (CTA) facoltativo
            $table->boolean('cta_enabled')->default(false);
            $table->string('cta_title')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_button_label')->nullable();
            $table->string('cta_button_url')->nullable();

            // Pubblicazione
            $table->boolean('is_published')->default(false);

            // Menu di navigazione e footer (opzionali, per pagina)
            $table->boolean('show_in_menu')->default(false);
            $table->string('menu_label')->nullable();
            $table->unsignedInteger('menu_order')->default(0);
            $table->boolean('show_in_footer')->default(false);
            $table->string('footer_label')->nullable();

            $table->foreignId('user_id')->nullable()
                  ->constrained('users')->nullOnDelete(); // autore

            $table->timestamps();

            $table->index('is_published');
            $table->index(['show_in_menu', 'menu_order']);
            $table->index(['show_in_footer', 'menu_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_pages');
    }
};
