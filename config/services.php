<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
        'meet_fixed_url' => env('GOOGLE_MEET_FIXED_URL'),
    ],

    // ── Stripe ────────────────────────────────────────────────────────────────
    'stripe' => [
        'key'             => env('STRIPE_KEY'),
        'secret'          => env('STRIPE_SECRET'),
        'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET'),
    ],

    // ── PayPal ────────────────────────────────────────────────────────────────
    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret'    => env('PAYPAL_SECRET'),
        // produzione: https://api-m.paypal.com
        // sandbox:    https://api-m.sandbox.paypal.com
        'base_url'  => env('PAYPAL_BASE_URL', 'https://api-m.sandbox.paypal.com'),
    ],

    // ── Banca (bonifico) ──────────────────────────────────────────────────────
    'bank' => [
        'iban'        => env('BANK_IBAN', ''),
        'intestatario' => env('BANK_INTESTATARIO', 'A&A Language Center Srl'),
    ],

];
