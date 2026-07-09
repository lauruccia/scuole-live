<?php

/*
|──────────────────────────────────────────────────────────────────────────────
| Redirect 301 — migrazione dal vecchio sito WordPress (aealanguagecenter.it)
|──────────────────────────────────────────────────────────────────────────────
| Mappa ogni URL indicizzata del vecchio sito WP/WooCommerce sulla pagina
| equivalente del nuovo sito Laravel. Serve a NON perdere l'indicizzazione
| Google quando il nuovo sito sostituisce il vecchio sullo stesso dominio.
|
| Fonte: sitemap Yoast del vecchio sito (sitemap_index.xml) letta il 2026-07-09
| (page-sitemap, post-sitemap, courses-sitemap, product-sitemap + tassonomie).
|
| NOTE TECNICHE
| - Il .htaccess standard di Laravel toglie già lo slash finale con un 301,
|   quindi "/la-scuola/" del vecchio sito arriva qui come "/la-scuola".
| - Route::permanentRedirect risponde 301 (permanente): è quello che serve a
|   Google per trasferire il ranking alla nuova URL.
| - Le route wildcard in fondo catturano intere sezioni WP (prodotti, corsi,
|   categorie, tag, autori) senza dover elencare ogni slug.
| - Questo file è incluso da routes/web.php. Le route qui NON devono mai
|   sovrapporsi a route reali del nuovo sito (nessuna collisione: sono tutti
|   path WP che nel nuovo sito non esistono).
*/

use Illuminate\Support\Facades\Route;

// ─── Pagine istituzionali ─────────────────────────────────────────────────────
Route::permanentRedirect('/privacy-policy', '/privacy');
Route::permanentRedirect('/scopri-tutti-i-servizi', '/servizi');
Route::permanentRedirect('/gli-insegnanti', '/la-scuola');
Route::permanentRedirect('/le-convenzioni', '/per-le-aziende');
Route::permanentRedirect('/corsi-per-le-aziende', '/corsi-aziendali-roma');
Route::permanentRedirect('/preparazione-esami-internazionali', '/le-certificazioni');

// ─── Catalogo corsi (pagine WP "tipologia corso") ─────────────────────────────
Route::permanentRedirect('/i-corsi', '/corsi');
Route::permanentRedirect('/corsi-per-adulti', '/corsi');
Route::permanentRedirect('/corsi-per-ragazzi', '/corsi');
Route::permanentRedirect('/corsi-per-bambini', '/corsi');
Route::permanentRedirect('/corsipersonalizzati-one-to-one', '/corsi');
Route::permanentRedirect('/corsi-in-presenza', '/corsi');
Route::permanentRedirect('/corsi-online', '/corsi');
Route::permanentRedirect('/inglese-al-telefono', '/corsi-inglese-roma');
Route::permanentRedirect('/vacanze-studio-2024', '/servizi');
Route::permanentRedirect('/traduzioni', '/servizi');

// ─── Test di livello ──────────────────────────────────────────────────────────
// Le 5 pagine test del vecchio sito ESISTONO ora sugli stessi slug nel nuovo
// sito (routes/web.php → test.livello / test.lingua): nessun redirect serve.
// Resta solo la sezione interna del plugin quiz WP (Quiz and Survey Master):
Route::get('/qsm_quiz/{any}', fn () => redirect('/test-sul-livello-di-lingua', 301))->where('any', '.*');

// ─── Corsi custom post type WP (/courses/...) ─────────────────────────────────
Route::permanentRedirect('/courses', '/corsi');
Route::permanentRedirect('/courses/corso-di-inglese', '/corsi-inglese-roma');
Route::permanentRedirect('/courses/corso-di-italiano-per-stranieri', '/corsi-italiano-stranieri-roma');
Route::get('/courses/{any}', fn () => redirect('/corsi', 301))->where('any', '.*');

// ─── Shop WooCommerce (scelta cliente: tutto → catalogo /corsi) ───────────────
Route::permanentRedirect('/shop-online', '/corsi');
Route::permanentRedirect('/shop-corsi-acquisto-online', '/corsi');
Route::permanentRedirect('/carrello', '/corsi');
Route::permanentRedirect('/il-mio-account', '/corsi');
Route::permanentRedirect('/il-mio-account-personale', '/corsi');
Route::permanentRedirect('/learning-compare', '/corsi');
Route::permanentRedirect('/learning-checkout', '/corsi');
Route::permanentRedirect('/learning-orders', '/corsi');
Route::get('/prodotto/{any}', fn () => redirect('/corsi', 301))->where('any', '.*');
Route::get('/categoria-prodotto/{any}', fn () => redirect('/corsi', 301))->where('any', '.*');
Route::get('/tag-prodotto/{any}', fn () => redirect('/corsi', 301))->where('any', '.*');
// NB: NON redirigiamo "/checkout" nudo perché il nuovo sito usa /checkout/{...}
// per i propri flussi di pagamento; il vecchio /checkout/ WP finisce comunque
// nel flusso nuovo o in 404 controllato.

// ─── Blog / notizie WP ────────────────────────────────────────────────────────
Route::permanentRedirect('/ultime-notizie', '/news');

// Articoli ricreati nel modulo News del nuovo sito (migration seed del
// 2026-07-09): redirect alla URL reale dell'articolo, non alla /news generica.
Route::permanentRedirect('/esami-trinity-college-2026', '/news/esami-trinity-college-2026');
Route::permanentRedirect('/certificazioni-lingua-inglese-riconosciute-dal-mim', '/news/certificazioni-lingua-inglese-riconosciute-dal-mim');
Route::permanentRedirect('/bonus-cultura-e-del-merito-per-i-nati-2006', '/news/carta-cultura-giovani-e-carta-del-merito');

// Altri articoli del vecchio blog (datati, non ricreati): → indice News.
// Se in futuro un articolo viene ricreato, spostarlo qui sopra con lo slug reale.
$oldPosts = [
    'abbiamo-riaperto',
    'yes-we-are-open',
    'do-not-stop-learning',
    'english-summer-school-2024-in-italy',
    'esami-in-videoconferenza-trinity-college-london-2024',
    'corsi-intensivi-preparazione-esami-ise',
];
foreach ($oldPosts as $slug) {
    Route::permanentRedirect('/' . $slug, '/news');
}

// ─── Tassonomie e archivi WP (categorie, tag, autori, insegnanti) ─────────────
Route::get('/category/{any}', fn () => redirect('/news', 301))->where('any', '.*');
Route::get('/tag/{any}', fn () => redirect('/news', 301))->where('any', '.*');
Route::get('/author/{any}', fn () => redirect('/news', 301))->where('any', '.*');
Route::get('/teachers/{any}', fn () => redirect('/la-scuola', 301))->where('any', '.*');
Route::get('/category-teacher/{any}', fn () => redirect('/la-scuola', 301))->where('any', '.*');
Route::get('/difficulty-level-course/{any}', fn () => redirect('/corsi', 301))->where('any', '.*');
Route::get('/category-course/{any}', fn () => redirect('/corsi', 301))->where('any', '.*');
Route::get('/typology-course/{any}', fn () => redirect('/corsi', 301))->where('any', '.*');
Route::get('/duration-course/{any}', fn () => redirect('/corsi', 301))->where('any', '.*');

// ─── Feed RSS WP ──────────────────────────────────────────────────────────────
Route::get('/feed', fn () => redirect('/news', 301));
Route::get('/ultime-notizie/feed', fn () => redirect('/news', 301));
