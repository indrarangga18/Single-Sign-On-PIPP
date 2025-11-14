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

    /*
    |--------------------------------------------------------------------------
    | PIPP Microservices Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for various PIPP microservices that will integrate
    | with the SSO system. Each service has its own URL and API key.
    |
    */

    'sahbandar' => [
        'url' => env('SAHBANDAR_SERVICE_URL', 'https://sahbandar.pipp.kkp.go.id'),
        'api_key' => env('SAHBANDAR_API_KEY'),
        'timeout' => 30,
    ],

    'spb' => [
        'url' => env('SPB_SERVICE_URL', 'https://spb.pipp.kkp.go.id'),
        'api_key' => env('SPB_API_KEY'),
        'timeout' => 30,
    ],

    'shti' => [
        'url' => env('SHTI_SERVICE_URL', 'https://shti.pipp.kkp.go.id'),
        'api_key' => env('SHTI_API_KEY'),
        'timeout' => 30,
    ],

    'epit' => [
        'url' => env('EPIT_SERVICE_URL', 'https://epit.pipp.kkp.go.id'),
        'api_key' => env('EPIT_API_KEY'),
        'timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | SSO Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Single Sign-On functionality
    |
    */

    'sso' => [
        'domain' => env('SSO_DOMAIN', 'pipp.kkp.go.id'),
        'callback_url' => env('SSO_CALLBACK_URL', 'https://pipp.kkp.go.id/auth/callback'),
        'session_lifetime' => env('SSO_SESSION_LIFETIME', 480), // 8 hours in minutes
        'remember_lifetime' => env('SSO_REMEMBER_LIFETIME', 10080), // 1 week in minutes
    ],

];