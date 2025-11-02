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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'ayenati' => [
        'base_url' => env('AYENATI_BASE_URL', 'https://default-url.com'),
        'carrierId' => env('AYENATI_CARRIER_ID', '1'),
    ],
    'blazma' => [
        'LOG_SECRETE_KEY' => env('LOG_SECRETE_KEY', "as_das#DA3AWR2313%432^3essd#@4_#$="),
        'LOG_HOSTS' => env('LOG_HOSTS', "http://158.101.243.250"),
        'BLAZMA_SECRET_KEY' => env('BLAZMA_SECRET_KEY', 'mtc&jZ4_om^%aR$2ARd3al_36I67D243-12&^%'),
    ],
];
