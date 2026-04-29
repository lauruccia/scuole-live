<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sentry DSN
    |--------------------------------------------------------------------------
    | Ottieni il DSN su sentry.io → Progetto → Settings → Client Keys.
    | Lasciare vuoto in locale per disabilitare l'invio degli eventi.
    |
    */
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    /*
    |--------------------------------------------------------------------------
    | Ambiente e Release
    |--------------------------------------------------------------------------
    */
    'environment' => env('APP_ENV', 'production'),
    'release'     => env('SENTRY_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Campionamento (Traces / Profiling)
    |--------------------------------------------------------------------------
    | traces_sample_rate: 1.0 = 100% delle transazioni, 0.1 = 10%.
    | In produzione impostare 0.1–0.2 per limitare i costi.
    |
    */
    'traces_sample_rate'   => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs
    |--------------------------------------------------------------------------
    */
    'breadcrumbs' => [
        'logs'            => true,
        'cache'           => true,
        'livewire'        => true,
        'sql_queries'     => true,
        'sql_bindings'    => false, // true solo per debug, mai in produzione
        'queue_info'      => true,
        'command_info'    => true,
        'http_client_requests' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Contesti utente
    |--------------------------------------------------------------------------
    | Invia l'ID utente (senza dati sensibili) per raggruppare gli errori.
    |
    */
    'send_default_pii' => false,

    /*
    |--------------------------------------------------------------------------
    | Ignore HTTP Status
    |--------------------------------------------------------------------------
    | Questi codici HTTP non vengono inviati a Sentry (es. 404, 403).
    |
    */
    'ignore_exceptions' => [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Illuminate\Validation\ValidationException::class,
    ],
];
