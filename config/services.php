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
    | Configuration for PIPP microservices integration
    |
    */

    'microservices' => [
        'sahbandar' => [
            'name' => 'Sahbandar Service',
            'base_url' => env('SAHBANDAR_SERVICE_URL', 'http://localhost:8001'),
            'api_key' => env('SAHBANDAR_API_KEY'),
            'timeout' => env('SAHBANDAR_TIMEOUT', 30),
            'retry_attempts' => env('SAHBANDAR_RETRY_ATTEMPTS', 3),
            'cache_ttl' => env('SAHBANDAR_CACHE_TTL', 300), // 5 minutes
            'health_check_endpoint' => '/api/health',
            'permissions' => [
                'sahbandar.view',
                'sahbandar.create',
                'sahbandar.update',
                'sahbandar.delete',
                'sahbandar.admin'
            ]
        ],

        'spb' => [
            'name' => 'SPB Service',
            'base_url' => env('SPB_SERVICE_URL', 'http://localhost:8002'),
            'api_key' => env('SPB_API_KEY'),
            'timeout' => env('SPB_TIMEOUT', 30),
            'retry_attempts' => env('SPB_RETRY_ATTEMPTS', 3),
            'cache_ttl' => env('SPB_CACHE_TTL', 300), // 5 minutes
            'health_check_endpoint' => '/api/health',
            'permissions' => [
                'spb.view',
                'spb.create',
                'spb.update',
                'spb.delete',
                'spb.admin'
            ]
        ],

        'shti' => [
            'name' => 'SHTI Service',
            'base_url' => env('SHTI_SERVICE_URL', 'http://localhost:8003'),
            'api_key' => env('SHTI_API_KEY'),
            'timeout' => env('SHTI_TIMEOUT', 30),
            'retry_attempts' => env('SHTI_RETRY_ATTEMPTS', 3),
            'cache_ttl' => env('SHTI_CACHE_TTL', 300), // 5 minutes
            'health_check_endpoint' => '/api/health',
            'permissions' => [
                'shti.view',
                'shti.create',
                'shti.update',
                'shti.delete',
                'shti.admin'
            ]
        ],

        'epit' => [
            'name' => 'EPIT Service',
            'base_url' => env('EPIT_SERVICE_URL', 'http://localhost:8004'),
            'api_key' => env('EPIT_API_KEY'),
            'timeout' => env('EPIT_TIMEOUT', 30),
            'retry_attempts' => env('EPIT_RETRY_ATTEMPTS', 3),
            'cache_ttl' => env('EPIT_CACHE_TTL', 300), // 5 minutes
            'health_check_endpoint' => '/api/health',
            'permissions' => [
                'epit.view',
                'epit.create',
                'epit.update',
                'epit.delete',
                'epit.admin'
            ]
        ],
    ],

    // Legacy service configuration (kept for backward compatibility)
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
        'token_ttl' => env('SSO_TOKEN_TTL', 3600), // 1 hour
        'max_sessions_per_user' => env('SSO_MAX_SESSIONS', 5),
        'cleanup_expired_sessions' => env('SSO_CLEANUP_EXPIRED', true),
        'cleanup_interval' => env('SSO_CLEANUP_INTERVAL', 3600), // 1 hour
        'allowed_services' => [
            'sahbandar',
            'spb',
            'shti',
            'epit'
        ],
        'service_callbacks' => [
            'sahbandar' => env('SAHBANDAR_CALLBACK_URL', 'http://localhost:8001/auth/sso/callback'),
            'spb' => env('SPB_CALLBACK_URL', 'http://localhost:8002/auth/sso/callback'),
            'shti' => env('SHTI_CALLBACK_URL', 'http://localhost:8003/auth/sso/callback'),
            'epit' => env('EPIT_CALLBACK_URL', 'http://localhost:8004/auth/sso/callback'),
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security-related configuration
    |
    */

    'security' => [
        'rate_limit' => [
            'login_attempts' => env('RATE_LIMIT_LOGIN', 5),
            'api_requests' => env('RATE_LIMIT_API', 60),
            'sso_requests' => env('RATE_LIMIT_SSO', 100),
        ],
        'password_policy' => [
            'min_length' => env('PASSWORD_MIN_LENGTH', 8),
            'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
            'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
            'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
            'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', false),
        ],
        'session_security' => [
            'secure_cookies' => env('SESSION_SECURE_COOKIES', true),
            'same_site' => env('SESSION_SAME_SITE', 'strict'),
            'http_only' => env('SESSION_HTTP_ONLY', true),
        ]
    ],

];