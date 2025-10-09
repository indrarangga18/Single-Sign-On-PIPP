<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        // Custom channels for SSO PIPP system
        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 90, // Keep security logs for 90 days
            'replace_placeholders' => true,
        ],

        'auth' => [
            'driver' => 'daily',
            'path' => storage_path('logs/auth.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 60, // Keep auth logs for 60 days
            'replace_placeholders' => true,
        ],

        'api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/api.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 30, // Keep API logs for 30 days
            'replace_placeholders' => true,
        ],

        'sso' => [
            'driver' => 'daily',
            'path' => storage_path('logs/sso.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 60, // Keep SSO logs for 60 days
            'replace_placeholders' => true,
        ],

        'microservices' => [
            'driver' => 'daily',
            'path' => storage_path('logs/microservices.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 30, // Keep microservice logs for 30 days
            'replace_placeholders' => true,
        ],

        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => env('LOG_LEVEL', 'info'),
            'days' => 14, // Keep performance logs for 14 days
            'replace_placeholders' => true,
        ],

        'database' => [
            'driver' => 'daily',
            'path' => storage_path('logs/database.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 7, // Keep database logs for 7 days
            'replace_placeholders' => true,
        ],

        'cache' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cache.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 7, // Keep cache logs for 7 days
            'replace_placeholders' => true,
        ],

        'errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/errors.log'),
            'level' => env('LOG_LEVEL', 'error'),
            'days' => 90, // Keep error logs for 90 days
            'replace_placeholders' => true,
        ],

        // Production monitoring channels
        'monitoring' => [
            'driver' => 'stack',
            'channels' => ['monitoring_file', 'monitoring_slack'],
            'ignore_exceptions' => false,
        ],

        'monitoring_file' => [
            'driver' => 'daily',
            'path' => storage_path('logs/monitoring.log'),
            'level' => env('LOG_LEVEL', 'warning'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        'monitoring_slack' => [
            'driver' => 'slack',
            'url' => env('MONITORING_SLACK_WEBHOOK_URL'),
            'username' => 'SSO PIPP Monitor',
            'emoji' => ':warning:',
            'level' => env('LOG_LEVEL', 'error'),
            'replace_placeholders' => true,
        ],

        // Audit channel for compliance
        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => 365, // Keep audit logs for 1 year for compliance
            'replace_placeholders' => true,
        ],
    ],

];