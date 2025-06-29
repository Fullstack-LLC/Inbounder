<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mailgun Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Mailgun webhook handling and signature verification.
    | Uses the same environment variables as Laravel's Mailgun mailer.
    |
    */
    'mailgun' => [
        // Standard Laravel Mailgun variables
        'secret' => env('MAILGUN_SECRET'),
        'domain' => env('MAILGUN_DOMAIN'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => env('MAILGUN_SCHEME', 'https'),

        // Webhook-specific variables for inbound email processing
        'signing_key' => env('MAILGUN_SIGNING_KEY'),
        'webhook_signing_key' => env('MAILGUN_WEBHOOK_SIGNING_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for custom model classes used by the package.
    |
    */
    'models' => [
        'user' => env('INBOUNDER_USER_MODEL', \App\Models\User::class),
        'tenant' => env('INBOUNDER_TENANT_MODEL', \App\Models\Tenant::class),
    ],

    /*
    |--------------------------------------------------------------------------
    | Attachment Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for handling email attachments.
    |
    */
    'attachments' => [
        'max_file_size' => env('INBOUNDER_MAX_ATTACHMENT_SIZE', 20 * 1024 * 1024), // 20MB default
        'storage_disk' => env('INBOUNDER_STORAGE_DISK', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for user authorization and permissions.
    |
    */
    'authorization' => [
        'required_permission' => env('INBOUNDER_REQUIRED_PERMISSION', 'can-send-emails'),
        'required_role' => env('INBOUNDER_REQUIRED_ROLE', 'tenant-admin'),
        'super_admin_roles' => [
            'super-admin',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for event dispatching and logging.
    |
    */
    'events' => [
        'dispatch_events' => env('INBOUNDER_DISPATCH_EVENTS', true),
        'log_events' => env('INBOUNDER_LOG_EVENTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for structured logging and monitoring.
    |
    */
    'logging' => [
        'enabled' => env('INBOUNDER_LOGGING_ENABLED', true),
        'channel' => env('INBOUNDER_LOG_CHANNEL', 'inbounder'),
        'level' => env('INBOUNDER_LOG_LEVEL', 'info'),
        'performance_tracking' => env('INBOUNDER_PERFORMANCE_TRACKING', true),
        'error_tracking' => env('INBOUNDER_ERROR_TRACKING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for email analytics and reporting.
    |
    */
    'analytics' => [
        'enabled' => env('INBOUNDER_ANALYTICS_ENABLED', true),
        'retention_days' => env('INBOUNDER_ANALYTICS_RETENTION_DAYS', 90),
        'real_time_metrics' => env('INBOUNDER_REAL_TIME_METRICS', true),
        'export_formats' => ['csv', 'json'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for package routes.
    |
    */
    'routes' => [
        'prefix' => env('INBOUNDER_ROUTE_PREFIX', 'api/webhooks/mailgun'),
        'middleware' => env('INBOUNDER_ROUTE_MIDDLEWARE', 'api'),
    ],
];
