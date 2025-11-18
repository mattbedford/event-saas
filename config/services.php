<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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
    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    ],

    'stripe' => [
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'chf'),
        'payment_methods' => ['card'],
    ],

    'hubspot' => [
        'api_key' => env('HUBSPOT_API_KEY'),
        'portal_id' => env('HUBSPOT_PORTAL_ID'),
        'timeout' => env('HUBSPOT_API_TIMEOUT', 10),
        'base_uri' => env('HUBSPOT_BASE_URI', 'https://api.hubapi.com/'),
        'default_company_limit' => env('HUBSPOT_DEFAULT_LIMIT', 100),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'from_email' => env('BREVO_FROM_EMAIL', 'noreply@example.com'),
        'from_name' => env('BREVO_FROM_NAME', config('app.name')),
    ],

    // API Token for admin endpoints
    'api_token' => env('API_TOKEN'),

];
