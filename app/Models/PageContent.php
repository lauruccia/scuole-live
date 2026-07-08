<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Contenuto editabile di una pagina pubblica (mini-CMS).
 *
 * I testi predefiniti vivono in config/site_contents.php: il DB contiene
 * SOLO i valori modificati dal pannello (Sito web → Contenuti sito).
 * Se la tabella non esiste ancora (migrate non eseguito) o una chiave
 * non è stata personalizzata, viene sempre usato il default di config —
 * il sito pubblico non può quindi mai rompersi per colpa di questo modulo.
 *
 * Uso nelle view Blade:
 *   {{ \App\Models\PageContent::text('home', 'hero_title') }}      ← escaped
 *   {!! \App\Models\PageContent::html('home', 'hero_desc') !!}     ← HTML raw
 *   {{ \App\Models\PageContent::image('home', 'hero_image') }}     ← URL immagine
 *   \App\Models\PageContent::items('home', 'faq_items')            ← array (FAQ)
 *   \App\Models\PageContent::lines('per-le-aziende', 'clienti')    ← array di righe
 */
class PageContent extends Model
{
    protected $table = 'page_contents';

    protected $fillable = ['page', 'key', 'value'];

    /** Cache runtime dei default per pagina (indice piatto key => default). */
    protected static array $defaultsIndex = [];

    /* ─── Lettura ────────────────────────────────────────────────────────── */

    /**
     * Tutti i valori personalizzati di una pagina (key => value), cache 5 min.
     * Ritorna [] se la tabella non esiste ancora o il DB non risponde.
     */
    public static function allFor(string $page): array
    {
        try {
            return Cache::remember("page_contents_{$page}", 300, function () use ($page) {
                return static::query()
                    ->where('page', $page)
                    ->whereNotNull('value')
                    ->pluck('value', 'key')
                    ->all();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Valore grezzo: personalizzato se presente e non vuoto, altrimenti default di config. */
    public static function raw(string $page, string $key, mixed $fallback = null): mixed
    {
        $custom = static::allFor($page)[$key] ?? null;
        if ($custom !== null && $custom !== '') {
            return $custom;
        }

        return static::defaultFor($page, $key) ?? $fallback;
    }

    /** Testo semplice (da stampare con {{ }}). */
    public static function text(string $page, string $key, ?string $fallback = null): string
    {
        return (string) static::raw($page, $key, $fallback);
    }

    /** Testo HTML (da stampare con {!! !!}). */
    public static function html(string $page, string $key, ?string $fallback = null): string
    {
        return (string) static::raw($page, $key, $fallback);
    }

    /**
     * URL immagine. Se il valore personalizzato è un path caricato dal pannello
     * (disk public, es. "pages/foto.jpg") viene convertito in URL /storage/...;
     * un URL assoluto (default di config o inserito a mano) passa invariato.
     */
    public static function image(string $page, string $key, ?string $fallback = null): string
    {
        $value = static::raw($page, $key, $fallback);
        if (! is_string($value) || $value === '') {
            return (string) $fallback;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }

        return asset('storage/' . ltrim($value, '/'));
    }

    /** Array strutturato (es. FAQ salvate come JSON dal repeater). */
    public static function items(string $page, string $key, array $fallback = []): array
    {
        $value = static::raw($page, $key);

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded) && $decoded !== []) {
                return $decoded;
            }
        }

        return is_array($value) && $value !== [] ? $value : $fallback;
    }

    /** Textarea "una voce per riga" → array di righe non vuote. */
    public static function lines(string $page, string $key, array $fallback = []): array
    {
        $value = static::raw($page, $key);
        if (! is_string($value) || trim($value) === '') {
            return $fallback;
        }

        $rows = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value))));

        return $rows !== [] ? $rows : $fallback;
    }

    /* ─── Default da config/site_contents.php ────────────────────────────── */

    /** Default di un campo, letto (e indicizzato una sola volta) dalla config. */
    public static function defaultFor(string $page, string $key): mixed
    {
        if (! isset(static::$defaultsIndex[$page])) {
            $index = [];
            foreach (config("site_contents.{$page}.sections", []) as $section) {
                foreach (($section['fields'] ?? []) as $fieldKey => $def) {
                    $index[$fieldKey] = $def['default'] ?? null;
                }
            }
            static::$defaultsIndex[$page] = $index;
        }

        return static::$defaultsIndex[$page][$key] ?? null;
    }

    /* ─── Scrittura (usata dal pannello Filament) ────────────────────────── */

    /** Salva un valore personalizzato e invalida la cache della pagina. */
    public static function put(string $page, string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['page' => $page, 'key' => $key],
            ['value' => $value],
        );
        static::forgetPage($page);
    }

    /** Rimuove la personalizzazione (la pagina torna al testo predefinito). */
    public static function reset(string $page, string $key): void
    {
        static::query()->where('page', $page)->where('key', $key)->delete();
        static::forgetPage($page);
    }

    public static function forgetPage(string $page): void
    {
        Cache::forget("page_contents_{$page}");
    }
}
