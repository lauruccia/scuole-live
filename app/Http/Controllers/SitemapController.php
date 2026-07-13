<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * SitemapController
 * ─────────────────────────────────────────────────────────────────────────────
 * Genera dinamicamente /sitemap.xml includendo:
 *   - tutte le pagine pubbliche statiche (home, la-scuola, servizi, ecc.)
 *   - le landing SEO per lingua (inglese, italiano stranieri, aziendali)
 *   - tutti i corsi attivi e pubblici dal DB
 *
 * Il risultato è cachato per 6 ore per non interrogare il DB ad ogni hit dei
 * crawler (Googlebot rivisita la sitemap di frequente).
 *
 * Standard XML: https://www.sitemaps.org/protocol.html
 */
class SitemapController extends Controller
{
    /**
     * TTL della cache. La sitemap non è dato sensibile, ma rigenerarla ad ogni
     * richiesta sarebbe spreco quando il catalogo cambia poche volte al mese.
     */
    private const CACHE_TTL_MINUTES = 360; // 6 ore
    private const CACHE_KEY = 'sitemap.xml';

    public function index(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_TTL_MINUTES), function () {
            return $this->buildXml();
        });

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('X-Robots-Tag', 'noindex'); // la sitemap stessa non va indicizzata
    }

    /**
     * Costruisce l'XML della sitemap.
     */
    private function buildXml(): string
    {
        $urls = $this->collectUrls();

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
        $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . PHP_EOL;

        foreach ($urls as $u) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($u['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8') . '</loc>' . PHP_EOL;
            if (! empty($u['lastmod'])) {
                $xml .= '    <lastmod>' . $u['lastmod'] . '</lastmod>' . PHP_EOL;
            }
            if (! empty($u['changefreq'])) {
                $xml .= '    <changefreq>' . $u['changefreq'] . '</changefreq>' . PHP_EOL;
            }
            if (isset($u['priority'])) {
                $xml .= '    <priority>' . number_format($u['priority'], 1) . '</priority>' . PHP_EOL;
            }
            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>' . PHP_EOL;

        return $xml;
    }

    /**
     * Raccoglie l'elenco delle URL da inserire.
     *
     * @return array<int,array{loc:string,lastmod?:string,changefreq?:string,priority?:float}>
     */
    private function collectUrls(): array
    {
        $today = now()->toIso8601String();

        // Pagine statiche pubbliche — priorità modellata sull'importanza SEO
        $static = [
            ['loc' => route('home'),            'changefreq' => 'weekly',  'priority' => 1.0, 'lastmod' => $today],
            ['loc' => route('la-scuola'),       'changefreq' => 'monthly', 'priority' => 0.9, 'lastmod' => $today],
            ['loc' => route('checkout.catalogo'),'changefreq' => 'daily',  'priority' => 0.9, 'lastmod' => $today],
            ['loc' => route('per-le-aziende'),  'changefreq' => 'monthly', 'priority' => 0.8, 'lastmod' => $today],
            ['loc' => route('servizi'),         'changefreq' => 'monthly', 'priority' => 0.8, 'lastmod' => $today],
            ['loc' => route('iscrizione'),      'changefreq' => 'monthly', 'priority' => 0.9, 'lastmod' => $today],
            ['loc' => route('contattaci'),      'changefreq' => 'monthly', 'priority' => 0.7, 'lastmod' => $today],
            ['loc' => route('lavora-con-noi'),  'changefreq' => 'monthly', 'priority' => 0.5, 'lastmod' => $today],
            ['loc' => route('le-certificazioni'), 'changefreq' => 'monthly', 'priority' => 0.8, 'lastmod' => $today],
            ['loc' => route('vacanze-studio'),  'changefreq' => 'monthly', 'priority' => 0.7, 'lastmod' => $today],
            ['loc' => route('insegnanti.index'),'changefreq' => 'monthly', 'priority' => 0.6, 'lastmod' => $today],
            ['loc' => route('news.index'),      'changefreq' => 'weekly',  'priority' => 0.7, 'lastmod' => $today],
            ['loc' => route('privacy'),         'changefreq' => 'yearly',  'priority' => 0.3, 'lastmod' => $today],
        ];

        // Landing SEO dedicate (registrate solo se le route esistono)
        $landings = [];
        foreach (['landing.inglese', 'landing.italiano-stranieri', 'landing.aziendali'] as $name) {
            if (\Route::has($name)) {
                $landings[] = [
                    'loc'        => route($name),
                    'changefreq' => 'weekly',
                    'priority'   => 0.9,
                    'lastmod'    => $today,
                ];
            }
        }

        // Test di livello online (hub + una pagina per lingua)
        $testPages = [];
        if (\Route::has('test.livello')) {
            $testPages[] = [
                'loc'        => route('test.livello'),
                'changefreq' => 'monthly',
                'priority'   => 0.8,
                'lastmod'    => $today,
            ];
        }
        if (\Route::has('test.lingua')) {
            foreach (array_keys(config('level_tests', [])) as $lingua) {
                $testPages[] = [
                    'loc'        => route('test.lingua', $lingua),
                    'changefreq' => 'monthly',
                    'priority'   => 0.8,
                    'lastmod'    => $today,
                ];
            }
        }

        // Corsi pubblici e attivi dal DB
        $courseUrls = [];
        try {
            $courses = Course::where('is_public', true)
                ->where('is_active', true)
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'updated_at']);

            foreach ($courses as $c) {
                $courseUrls[] = [
                    'loc'        => route('checkout.show', $c->id),
                    'changefreq' => 'monthly',
                    'priority'   => 0.7,
                    'lastmod'    => optional($c->updated_at)->toIso8601String() ?? $today,
                ];
            }
        } catch (\Throwable $e) {
            // Se per qualche motivo il DB non risponde non rompiamo la sitemap:
            // ritorniamo almeno le pagine statiche.
            report($e);
        }

        // News ed eventi pubblicati
        $newsUrls = [];
        try {
            $posts = \App\Models\NewsPost::published()->get(['slug', 'updated_at']);

            foreach ($posts as $p) {
                $newsUrls[] = [
                    'loc'        => route('news.show', $p->slug),
                    'changefreq' => 'monthly',
                    'priority'   => 0.6,
                    'lastmod'    => optional($p->updated_at)->toIso8601String() ?? $today,
                ];
            }
        } catch (\Throwable $e) {
            // Tabella non ancora migrata o DB non disponibile: non rompiamo la sitemap.
            report($e);
        }

        // Profili insegnanti pubblicati
        $teacherUrls = [];
        try {
            $teachers = \App\Models\TeacherProfile::published()->get(['slug', 'updated_at']);

            foreach ($teachers as $t) {
                $teacherUrls[] = [
                    'loc'        => route('insegnanti.show', $t->slug),
                    'changefreq' => 'yearly',
                    'priority'   => 0.5,
                    'lastmod'    => optional($t->updated_at)->toIso8601String() ?? $today,
                ];
            }
        } catch (\Throwable $e) {
            // Tabella non ancora migrata o DB non disponibile: non rompiamo la sitemap.
            report($e);
        }

        return array_merge($static, $landings, $testPages, $courseUrls, $newsUrls, $teacherUrls);
    }
}
