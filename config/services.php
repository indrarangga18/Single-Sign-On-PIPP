<?php

if(!function_exists('env')){function env($key,$default=null){$v=$_ENV[$key]??getenv($key);if($v===false||$v===null){return $default;}return $v;}}
if(!function_exists('app_env')){function app_env($key,$default=null){$v=$_ENV[$key]??getenv($key);if($v===false||$v===null){return $default;}return $v;}}

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
        'domain' => app_env('MAILGUN_DOMAIN'),
        'secret' => app_env('MAILGUN_SECRET'),
        'endpoint' => app_env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => app_env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => app_env('AWS_ACCESS_KEY_ID'),
        'secret' => app_env('AWS_SECRET_ACCESS_KEY'),
        'region' => app_env('AWS_DEFAULT_REGION', 'us-east-1'),
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
        'url' => app_env('SAHBANDAR_SERVICE_URL', 'https://sahbandar.pipp.kkp.go.id'),
        'api_key' => app_env('SAHBANDAR_API_KEY'),
        'timeout' => 30,
    ],

    'spb' => [
        'url' => app_env('SPB_SERVICE_URL', 'https://spb.pipp.kkp.go.id'),
        'api_key' => app_env('SPB_API_KEY'),
        'timeout' => 30,
    ],

    'shti' => [
        'url' => app_env('SHTI_SERVICE_URL', 'https://shti.pipp.kkp.go.id'),
        'api_key' => app_env('SHTI_API_KEY'),
        'timeout' => 30,
    ],

    'epit' => [
        'url' => app_env('EPIT_SERVICE_URL', 'https://epit.pipp.kkp.go.id'),
        'api_key' => app_env('EPIT_API_KEY'),
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
        'domain' => app_env('SSO_DOMAIN', 'pipp.kkp.go.id'),
        'callback_url' => app_env('SSO_CALLBACK_URL', 'https://pipp.kkp.go.id/auth/callback'),
        'session_lifetime' => app_env('SSO_SESSION_LIFETIME', 480),
        'remember_lifetime' => app_env('SSO_REMEMBER_LIFETIME', 10080),
    ],

];