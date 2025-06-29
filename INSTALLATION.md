# Local Installation Guide

This guide shows how to install and use the Inbounder package locally in your Laravel project.

## Step 1: Add Local Repository

Add this to your main `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/redbird/inbounder"
        }
    ]
}
```

## Step 2: Install the Package

```bash
composer require fullstack/inbounder
```

## Step 3: Publish Configuration

```bash
php artisan vendor:publish --tag=inbounder-config
```

## Step 4: Publish Migrations

```bash
php artisan vendor:publish --tag=inbounder-migrations
php artisan migrate
```

## Step 5: Update Environment Variables

Add these to your `.env` file:

```env
# Standard Laravel Mailgun Configuration (for sending emails)
MAILGUN_SECRET=your-mailgun-api-key
MAILGUN_DOMAIN=your-mailgun-domain
MAILGUN_ENDPOINT=api.mailgun.net
MAILGUN_SCHEME=https

# Inbounder-specific Mailgun Configuration (for receiving webhooks)
MAILGUN_SIGNING_KEY=your-mailgun-signing-key
MAILGUN_WEBHOOK_SIGNING_KEY=your-webhook-signing-key

# Inbounder Configuration
INBOUNDER_MAX_ATTACHMENT_SIZE=20971520
INBOUNDER_STORAGE_DISK=local
INBOUNDER_REQUIRED_PERMISSION=can-send-emails
INBOUNDER_REQUIRED_ROLE=tenant-admin
INBOUNDER_DISPATCH_EVENTS=true
INBOUNDER_LOG_EVENTS=true

# Logging Configuration
INBOUNDER_LOGGING_ENABLED=true
INBOUNDER_LOG_CHANNEL=inbounder
INBOUNDER_LOG_LEVEL=info
INBOUNDER_PERFORMANCE_TRACKING=true
INBOUNDER_ERROR_TRACKING=true

# Analytics Configuration
INBOUNDER_ANALYTICS_ENABLED=true
INBOUNDER_ANALYTICS_RETENTION_DAYS=90
INBOUNDER_REAL_TIME_METRICS=true
```

## Step 6: Configure Logging Channel

Add this to your `config/logging.php`:

```php
'channels' => [
    // ... other channels

    'inbounder' => [
        'driver' => 'daily',
        'path' => storage_path('logs/inbounder.log'),
        'level' => env('INBOUNDER_LOG_LEVEL', 'info'),
        'days' => 14,
    ],
],
```

## Step 7: Run Migrations

```bash
php artisan migrate
```

## Step 8: Test the Installation

The package will automatically register these routes:

- **Webhook**: `POST /api/webhooks/mailgun` - Handles Mailgun webhooks
- **Analytics**: `GET /api/webhooks/mailgun/analytics` - Get email analytics
- **Monitoring**: `GET /api/webhooks/mailgun/monitoring/health` - Health check

You can test it by sending an email to your Mailgun domain and checking if it's processed correctly.

## Available API Endpoints

### Analytics Endpoints
- `GET /api/webhooks/mailgun/analytics` - Get analytics for a date range
- `GET /api/webhooks/mailgun/analytics/realtime` - Get real-time metrics
- `GET /api/webhooks/mailgun/analytics/export` - Export analytics to CSV
- `GET /api/webhooks/mailgun/analytics/senders` - Get top senders
- `GET /api/webhooks/mailgun/analytics/geography` - Get geographic distribution
- `GET /api/webhooks/mailgun/analytics/devices` - Get device distribution

### Monitoring Endpoints
- `GET /api/webhooks/mailgun/monitoring/health` - Health check
- `GET /api/webhooks/mailgun/monitoring/alerts` - Get system alerts
- `GET /api/webhooks/mailgun/monitoring/performance` - Get performance metrics
- `GET /api/webhooks/mailgun/monitoring/status` - Get system status
