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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],
    
    'mailchimp' => [
        'api_key' => env('MAILCHIMP_API_KEY'),
        'server' => env('MAILCHIMP_SERVER', 'us17'),
        'audience_id' => env('MAILCHIMP_AUDIENCE_ID'),
        'from_name' => env('MAILCHIMP_FROM_NAME', config('app.name')),
        'reply_to' => env('MAILCHIMP_REPLY_TO'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'bridge' => [
        'base_uri' => env('BRIDGE_BASE_URI'),
        'api_key'  => env('BRIDGE_API_KEY'),
    ],
    'alfred' => [
        'base_uri' => env('ALFRED_BASE_URI'),
        'api_key'  => env('ALFRED_API_KEY'),
        'api_secret' => env('ALFRED_API_SECRET'),
    ],
    'shortio' => [
        'api_key' => env('SHORTIO_API_KEY', 'sk_OHtFayXvRmQdzwS5'),
        'domain' => env('SHORTIO_DOMAIN', 'example.xyz'),
    ],
];
