<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mailgun Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Mailgun integration including
    | API credentials, webhook settings, and domain configuration.
    |
    */

    /** The Mailgun domain. */
    'domain' => env('MAILGUN_DOMAIN'),

    /** The Mailgun secret. */
    'secret' => env('MAILGUN_SECRET'),

    /** The Mailgun endpoint. */
    'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),

    /** The webhook signing key. */
    'webhook_signing_key' => env('MAILGUN_WEBHOOK_SIGNING_KEY'),

    /** Whether to force signature testing. */
    'force_signature_testing' => false,

    /*
    |--------------------------------------------------------------------------
    | Outbound Email Settings
    |--------------------------------------------------------------------------
    |
    | Configure outbound email sending settings.
    |
    */
    'outbound' => [

        /** Whether to enable outbound email sending. */
        'enabled' => true,

        /** The default from address and name. */
        'default_from' => [
            'address' => env('MAIL_FROM_ADDRESS'),
            'name' => env('MAIL_FROM_NAME'),
        ],

        /** Whether to track opens, clicks, and unsubscribes. */
        'tracking' => [
            'opens' => true,
            'clicks' => true,
            'unsubscribes' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Configure webhook behavior and security settings.
    |
    */

    'webhook' => [
        'verify_signature' => true,
        'timestamp_tolerance' => 300, // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Inbound Email Settings
    |--------------------------------------------------------------------------
    |
    | Configure inbound email processing settings.
    |
    */

    'inbound' => [
        'store_attachments' => true,
        'max_attachment_size' => 10485760, // 10MB
        'allowed_file_types' => [
            'pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif',
        ],
        'store_inbound' => [
            'enabled' => false,
            'model' => Inbounder\Models\MailgunInboundEmail::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Configure logging behavior for Mailgun events.
    |
    */

    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'channel' => 'stack',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Storage Settings
    |--------------------------------------------------------------------------
    |
    | Configure database storage behavior for Mailgun events and inbound emails.
    |
    */

    'database' => [

        /** Whether to store webhook events in the database. */
        'webhooks' => [
            /** Whether to store webhook events in the database. */
            'enabled' => true,

            /** The model to use for storing webhook events. */
            'model' => Inbounder\Models\MailgunEvent::class,

            /** The events to store in the database. */
            'store_events' => [
                'accepted',
                'delivered',
                'bounced',
                'complained',
                'unsubscribed',
                'opened',
                'clicked',
            ],
        ],

        /** Whether to store inbound emails in the database. */
        'inbound' => [
            'enabled' => true,
            'model' => Inbounder\Models\MailgunInboundEmail::class,
        ],

        /** Whether to store outbound emails in the database. */
        'outbound' => [
            /** Whether to store outbound emails in the database. */
            'enabled' => true,

            /** The model to use for storing outbound emails. */
            'model' => Inbounder\Models\MailgunOutboundEmail::class,

            /** The events to track. */
            'track_events' => [
                'accepted',
                'delivered',
                'opened',
                'clicked',
                'bounced',
                'complained',
                'unsubscribed',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Settings
    |--------------------------------------------------------------------------
    |
    | Configure event names for Mailgun-related events that can be listened to.
    |
    */
    'events' => [
        'inbound_email_received' => 'mailgun.inbound.email.received',
        'webhook_event_received' => 'mailgun.webhook.event.received',
        'distribution_list_created' => 'mailgun.distribution_list.created',
        'distribution_list_updated' => 'mailgun.distribution_list.updated',
        'distribution_list_deleted' => 'mailgun.distribution_list.deleted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Event Settings
    |--------------------------------------------------------------------------
    |
    | Configure which webhook events should trigger the WebhookEventReceived event.
    | Set to empty array to disable all webhook events, or specify individual events.
    |
    */

    'webhook_events' => [
        /** Whether to enable webhook events. */
        'enabled' => true,

        /** The events to trigger. */
        'trigger_events' => [
            'delivered' => true,
            'bounced' => true,
            'complained' => true,
            'unsubscribed' => true,
            'opened' => true,
            'clicked' => true,
            'accepted' => true,
            'rejected' => true,
            'dropped' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Settings
    |--------------------------------------------------------------------------
    |
    | Configure how to check if a user is allowed to send emails.
    | Options: 'gate', 'policy', 'spatie'
    |
    */
    'authorization' => [
        /**
         * The method to use for authorization.
         * Options: 'gate', 'policy', 'spatie'
         */
        'method' => 'gate',

        /** The gate name to use for authorization. */
        'gate_name' => 'send-email',

        /** The policy method to use for authorization. */
        'policy_method' => 'sendEmail',

        /** The spatie permission to use for authorization. */
        'spatie_permission' => 'send-email',
    ],



    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Configure queue settings for email processing jobs.
    | This allows you to use dedicated queues for email processing
    | to avoid interfering with other application queues.
    |
    */
    'queue' => [
        /** Whether to use custom queue settings. */
        'enabled' => false,

        /** The default queue name for email jobs. */
        'default_queue' => 'mailgun',

        /** Queue settings for different job types. */
        'queues' => [
            /** Queue for sending templated emails. */
            'templated_emails' => 'mailgun-emails',

            /** Queue for processing webhook events. */
            'webhook_events' => 'mailgun-webhooks',

            /** Queue for processing inbound emails. */
            'inbound_emails' => 'mailgun-inbound',

            /** Queue for tracking and analytics jobs. */
            'tracking' => 'mailgun-tracking',
        ],

        /** Connection settings for queues. */
        'connection' => [
            /** The queue connection to use. */
            'driver' => 'default',

            /** Queue retry settings. */
            'retry' => [
                /** Maximum number of retry attempts. */
                'max_attempts' => 3,

                /** Delay between retries in seconds. */
                'delay' => 60,

                /** Whether to backoff retries exponentially. */
                'backoff' => true,
            ],

            /** Queue timeout settings. */
            'timeout' => [
                /** Job timeout in seconds. */
                'job_timeout' => 300,

                /** Queue timeout in seconds. */
                'queue_timeout' => 600,
            ],
        ],

        /** Batch processing settings. */
        'batch' => [
            /** Whether to enable batch processing for multiple emails. */
            'enabled' => true,

            /** Maximum batch size for email jobs. */
            'max_size' =>100,

            /** Delay between batches in seconds. */
            'delay' => 5,
        ],
    ],

];
