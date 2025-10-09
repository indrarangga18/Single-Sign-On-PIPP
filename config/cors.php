<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sso/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Production domains
        'https://sahbandar.pipp.go.id',
        'https://spb.pipp.go.id',
        'https://shti.pipp.go.id',
        'https://epit.pipp.go.id',
        'https://sso.pipp.go.id',
        
        // Development domains
        'http://localhost:3000',
        'http://localhost:8080',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8080',
        'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [
        // Allow subdomains in development
        '/^http:\/\/.*\.localhost(:\d+)?$/',
        '/^http:\/\/.*\.127\.0\.0\.1(:\d+)?$/',
        
        // Allow PIPP subdomains in production
        '/^https:\/\/.*\.pipp\.go\.id$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
        'X-SSO-Token',
        'X-Service-Name',
    ],

    'max_age' => 0,

    'supports_credentials' => true,

];