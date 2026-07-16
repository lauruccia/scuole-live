<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

/**
 * CustomPage — pagina pubblica creata liberamente dallo staff dal pannello
 * (page-builder semplificato: Hero + corpo rich-text libero + CTA opzionale),
 * pubblicata su un URL a scelta root-level (es. /nome-pagina).
 *
 * Diversa da "Contenuti sito" (PageContent): quel modulo modifica pagine GIÀ
 * esistenti nel codice (testi/immagini di template predefiniti). Questo
 * modulo invece CREA pagine nuove da zero, con slug scelto dallo staff —
 * stesso pattern "modulo pubblico gestito da Filament" di NewsPost
 * (articoli/eventi) e TeacherProfile (profili docenti pubblici).
 *
 * Lo slug root-level va sempre validato contro le route esistenti (vedi
 * reservedSlugs()) per non spegnere accidentalmente una pagina reale del
 * sito (es. creare una pagina con slug "servizi" romperebbe /servizi) — la
 * route pubblica /{slug} che serve queste pagine è registrata per ultima in
 * routes/web.php proprio per restare un fallback a bassa priorità.
 */
class CustomPage extends Model
{
    protected $table = 'custom_pages';

    protected $fillable = [
        'title', 'slug', 'meta_title', 'meta_description',
        'hero_title', 'hero_subtitle', 'hero_image', 'body',
        'cta_enabled', 'cta_title', 'cta_text', 'cta_button_label', 'cta_button_url',
        'is_published', 'show_in_menu', 'menu_label', 'menu_order',
        'show_in_footer', 'footer_label', 'user_id',
    ];

    protected $casts = [
        'is_published'   => 'boolean',
        'cta_enabled'    => 'boolean',
        'show_in_menu'   => 'boolean',
        'show_in_footer' => 'boolean',
        'menu_order'     => 'integer',
    ];

    protected static function booted(): void
    {
        // Il menu/footer del layout pubblico è cachato 5 min (vedi menuItems()
        // / footerItems()): invalidiamo subito ad ogni salvataggio/cancellazione
        // così una modifica dal pannello non resta "in ritardo" fino a 5 min.
        static::saved(fn () => static::forgetMenuCache());
        static::deleted(fn () => static::forgetMenuCache());
    }

    // ─── Relazioni ───────────────────────────────────────────────────────────

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Scope ───────────────────────────────────────────────────────────────

    /** Solo pagine pubblicate. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** Pagine pubblicate da mostrare nel menu di navigazione, in ordine. */
    public function scopeInMenu(Builder $query): Builder
    {
        return $query->published()->where('show_in_menu', true)->orderBy('menu_order')->orderBy('title');
    }

    /** Pagine pubblicate da mostrare nel footer, in ordine. */
    public function scopeInFooter(Builder $query): Builder
    {
        return $query->published()->where('show_in_footer', true)->orderBy('menu_order')->orderBy('title');
    }

    // ─── Menu / footer (letti dal layout pubblico, cache 5 min) ───────────────

    /**
     * Voci pubblicate per il menu di navigazione.
     * try/catch: se la tabella non è ancora migrata (deploy codice prima
     * della migration) il layout pubblico non deve rompersi.
     */
    public static function menuItems(): iterable
    {
        try {
            return Cache::remember('custom_pages_menu', 300, function () {
                return static::inMenu()->get(['id', 'title', 'slug', 'menu_label']);
            });
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /** Voci pubblicate per il footer. Stessa protezione di menuItems(). */
    public static function footerItems(): iterable
    {
        try {
            return Cache::remember('custom_pages_footer', 300, function () {
                return static::inFooter()->get(['id', 'title', 'slug', 'footer_label']);
            });
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public static function forgetMenuCache(): void
    {
        Cache::forget('custom_pages_menu');
        Cache::forget('custom_pages_footer');
    }

    // ─── Slug: generazione e protezione da collisioni con route reali ────────

    /** Genera uno slug unico (e non riservato) a partire dal titolo. */
    public static function generateSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'pagina';
        $slug = $base;
        $i = 2;
        while (! static::isSlugAvailable($slug, $ignoreId)) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    /** True se lo slug NON è riservato (route esistente) e non è già usato da un'altra pagina. */
    public static function isSlugAvailable(string $slug, ?int $ignoreId = null): bool
    {
        $slug = strtolower(trim($slug, '/'));

        if ($slug === '' || static::isSlugReserved($slug)) {
            return false;
        }

        return ! static::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    protected static ?array $reservedSlugsCache = null;

    /**
     * Slug "riservati" perché corrispondono a una route reale del sito.
     * Calcolato DINAMICAMENTE dalle route registrate (statiche, un solo
     * segmento, metodo GET, senza parametri) così resta sempre aggiornato
     * senza bisogno di tenere una lista manuale in sincro col resto del
     * sito — se domani si aggiunge una nuova pagina "vera" in routes/web.php
     * o routes/redirects.php, diventa automaticamente riservata anche qui.
     */
    public static function reservedSlugs(): array
    {
        if (static::$reservedSlugsCache !== null) {
            return static::$reservedSlugsCache;
        }

        $reserved = [];
        foreach (RouteFacade::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }
            $uri = trim($route->uri(), '/');
            if ($uri !== '' && ! str_contains($uri, '/') && ! str_contains($uri, '{')) {
                $reserved[] = strtolower($uri);
            }
        }

        // Parole riservate ulteriori, non necessariamente route Laravel
        // (asset statici, percorsi di sistema, route d'autenticazione di
        // default anche se non tutte in uso in questo progetto).
        $reserved = array_unique(array_merge($reserved, [
            'admin', 'storage', 'build', 'api', 'up',
            'sitemap.xml', 'robots.txt', 'favicon.ico',
            'login', 'logout', 'register', 'password', 'email', 'dashboard',
        ]));

        return static::$reservedSlugsCache = $reserved;
    }

    public static function isSlugReserved(string $slug): bool
    {
        return in_array(strtolower(trim($slug, '/')), static::reservedSlugs(), true);
    }
}
