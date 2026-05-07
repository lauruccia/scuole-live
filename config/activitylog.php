<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tabella Activity Log
    |--------------------------------------------------------------------------
    */
    'table_name'            => env('ACTIVITY_LOG_TABLE', 'activity_log'),
    'database_connection'   => env('ACTIVITY_LOG_DB_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Auto-pulizia
    |--------------------------------------------------------------------------
    | I log più vecchi di questo numero di giorni vengono rimossi da:
    |   php artisan activitylog:clean
    | Impostare a null per non eliminare mai.
    |
    | 730 giorni (2 anni) è il default ragionevole per un audit log
    | che traccia dati GDPR e finanziari: copre la maggior parte delle
    | richieste di accesso (Subject Access Request) e debug post-incident,
    | senza far esplodere la tabella.
    |
    | Override per ambiente con: ACTIVITY_LOG_RETENTION_DAYS=...
    | (per disattivare la pulizia in produzione, mettere a stringa "null"
    | nel .env e gestirla a livello applicativo).
    |
    */
    'delete_records_older_than_days' => env('ACTIVITY_LOG_RETENTION_DAYS', 730),

    /*
    |--------------------------------------------------------------------------
    | Modello predefinito
    |--------------------------------------------------------------------------
    */
    'activity_model' => \Spatie\Activitylog\Models\Activity::class,

    /*
    |--------------------------------------------------------------------------
    | Batch UUID
    |--------------------------------------------------------------------------
    | Abilita il raggruppamento di più eventi in un singolo "batch".
    |
    */
    'default_log_name' => 'default',
];
