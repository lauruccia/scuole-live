<?php

/*
|--------------------------------------------------------------------------
| Configurazione SEO — A&A Language Center
|--------------------------------------------------------------------------
|
| Tutte le chiavi qui sotto sono opzionali. Lasciandole vuote (o non
| settando la relativa variabile .env) i rispettivi snippet NON vengono
| stampati nel layout, quindi nessuno script terzo viene caricato.
|
| Centralizzando qui le ID si evita di toccare le view per attivarli.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Google
    |--------------------------------------------------------------------------
    | - GA4_ID: misurazione tipo "G-XXXXXXXXXX"
    | - GTM_ID: Google Tag Manager tipo "GTM-XXXXXXX" (alternativa a GA4)
    | - SEARCH_CONSOLE_VERIFICATION: codice di verifica meta-tag
    */
    'ga4_id'                    => env('SEO_GA4_ID'),
    'gtm_id'                    => env('SEO_GTM_ID'),
    'google_site_verification'  => env('SEO_GOOGLE_SITE_VERIFICATION'),

    /*
    |--------------------------------------------------------------------------
    | Bing / Microsoft Webmaster
    |--------------------------------------------------------------------------
    */
    'bing_site_verification'    => env('SEO_BING_SITE_VERIFICATION'),

    /*
    |--------------------------------------------------------------------------
    | Facebook / Meta
    |--------------------------------------------------------------------------
    | - PIXEL_ID: ID del Meta (Facebook) Pixel
    | - DOMAIN_VERIFICATION: codice per verifica dominio Meta Business
    */
    'facebook_pixel_id'         => env('SEO_FACEBOOK_PIXEL_ID'),
    'facebook_domain_verification' => env('SEO_FACEBOOK_DOMAIN_VERIFICATION'),

    /*
    |--------------------------------------------------------------------------
    | Default sitemap settings
    |--------------------------------------------------------------------------
    */
    'sitemap' => [
        'default_priority'   => 0.7,
        'default_changefreq' => 'weekly',
    ],

];
