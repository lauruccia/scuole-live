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
    */
    'delete_records_older_than_days' => 90,

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
