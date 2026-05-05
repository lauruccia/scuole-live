<?php

/**
 * Configurazione spatie/laravel-backup per ScuoleLive.
 *
 * Strategia:
 *   - Backup giornaliero alle 02:00 (database + file essenziali)
 *   - Destinazione primaria: disco locale "backups" (storage/app/backups)
 *   - Destinazione secondaria: S3 se configurato (AWS_BUCKET)
 *   - Pulizia automatica: mantieni ultimi 7 giornalieri, 4 settimanali, 3 mensili
 *   - Notifica email su fallimento (BACKUP_NOTIFICATION_EMAIL)
 */
return [

    'backup' => [

        /*
         * Nome del backup — compare nel nome del file .zip
         */
        'name' => env('APP_NAME', 'ScuoleLive'),

        'source' => [

            'files' => [
                /*
                 * File e cartelle da includere nel backup.
                 * Includiamo: .env, storage/app (materiali didattici), config.
                 * Escludiamo: vendor, node_modules, storage/logs (voluminosi e non critici).
                 */
                'include' => [
                    base_path('.env'),
                    config_path(),
                    storage_path('app'),
                ],

                'exclude' => [
                    storage_path('app/backups'),   // evita loop ricorsivo
                    storage_path('app/public/tmp'),
                ],

                'follow_links' => false,

                'ignore_unreadable_directories' => true,

                'relative_path' => base_path(),
            ],

            'databases' => [
                'mysql', // usa la connessione DB_CONNECTION di default
            ],
        ],

        'database_dump_compressor' => null,

        'database_dump_file_extension' => '',

        'destination' => [

            /*
             * Dischi dove salvare il backup.
             * 'local-backups' → storage/app/backups (sempre attivo)
             * 's3'            → bucket S3 se AWS_BUCKET è configurato
             */
            'disks' => array_filter([
                'local-backups',
                env('AWS_BUCKET') ? 's3' : null,
            ]),

            'filename_prefix' => '',
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        'password' => null,

        'encryption' => 'default',

        'tries' => 1,
        'retry_delay' => 0,
    ],

    'notifications' => [

        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification::class => ['mail'],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => [], // silenzioso
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => [], // silenzioso
            \Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => [], // silenzioso
        ],

        'notifiable' => \Spatie\Backup\Notifications\Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@example.com')),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'name'    => env('MAIL_FROM_NAME', env('APP_NAME', 'ScuoleLive')),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel'     => null,
            'username'    => null,
            'icon'        => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username'    => '',
            'avatar_url'  => '',
        ],
    ],

    'monitor_backups' => [
        [
            'name'                        => env('APP_NAME', 'ScuoleLive'),
            'disks'                       => ['local-backups'],
            // Avvisa se il backup più recente è più vecchio di 25 ore
            'health_checks'               => [
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays::class     => 1,
                \Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    'cleanup' => [
        /*
         * Strategia di pulizia: conserva un numero limitato di backup
         * per non saturare il disco.
         */
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days'                 => 7,   // tutti i backup degli ultimi 7 giorni
            'keep_daily_backups_for_days'               => 16,  // 1 giornaliero per 16 giorni
            'keep_weekly_backups_for_weeks'             => 8,   // 1 settimanale per 8 settimane
            'keep_monthly_backups_for_months'           => 4,   // 1 mensile per 4 mesi
            'keep_yearly_backups_for_years'             => 1,   // 1 annuale per 1 anno
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
    ],
];
