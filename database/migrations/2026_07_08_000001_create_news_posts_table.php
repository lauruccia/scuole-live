<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modulo News & Eventi
 * ─────────────────────────────────────────────────────────────────────────────
 * Notizie ed eventi pubblicabili dallo staff (Amministrazione + Segreteria),
 * mostrati sul sito pubblico (/news) e in home page — stile blog WordPress.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type', 20)->default('news');          // news | evento
            $table->string('excerpt', 500)->nullable();            // riassunto per card/anteprima
            $table->longText('body');                              // contenuto HTML (RichEditor)
            $table->string('cover_image')->nullable();             // path su disk "public"
            $table->date('event_date')->nullable();                // solo per type=evento
            $table->string('event_location')->nullable();          // solo per type=evento
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('user_id')->nullable()
                  ->constrained('users')->nullOnDelete();          // autore
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_posts');
    }
};
