<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mailgun Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Mailgun webhook handling and signature verification.
    |
    */
    'mailgun' => [
        'signing_key' => env('MAILGUN_SIGNING_KEY'),
        'webhook_signing_key' => env('MAILGUN_WEBHOOK_SIGNING_KEY'),
        'domain' => env('MAILGUN_DOMAIN'),
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
        'storage_path' => env('INBOUNDER_STORAGE_PATH', 'inbound-emails/attachments'),
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
    | Route Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for package routes.
    |
    */
    'routes' => [
        'prefix' => env('INBOUNDER_ROUTE_PREFIX', 'api/mail/mailgun'),
        'middleware' => env('INBOUNDER_ROUTE_MIDDLEWARE', 'api'),
    ],
];
