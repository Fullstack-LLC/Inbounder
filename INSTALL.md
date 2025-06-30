# Installation Guide

This guide will walk you through installing and configuring the Laravel Inbounder package step by step.

## Prerequisites

- Laravel 10.x or 11.x
- PHP 8.1 or higher
- Composer
- Mailgun account with webhook access

## Step 1: Install the Package

Install the package via Composer:

```bash
composer require fullstack/inbounder
```

## Step 2: Publish Configuration

Publish the configuration file to your Laravel application:

```bash
php artisan vendor:publish --provider="Fullstack\Inbounder\InbounderServiceProvider" --tag="config"
```

This creates `config/inbounder.php` in your Laravel application.

## Step 3: Configure Environment Variables

Add the following to your `.env` file:

```env
MAILGUN_SIGNING_SECRET=your_mailgun_signing_secret_here
```

### Finding Your Mailgun Signing Secret

1. Log into your Mailgun dashboard
2. Go to **Settings** → **Webhooks**
3. Copy the signing secret from your webhook configuration

## Step 4: Run Database Migrations

The package uses the `spatie/laravel-webhook-client` package which requires database tables. Run:

```bash
# Publish the webhook client migrations
php artisan vendor:publish --provider="Spatie\WebhookClient\WebhookClientServiceProvider" --tag="migrations"

# Run the migrations
php artisan migrate
```

## Step 5: Register Routes

Add the webhook route to your `routes/web.php` or `routes/api.php`:

```php
Route::inbounder('webhooks/mailgun');
```

This creates a POST route at `/webhooks/mailgun` that will handle incoming Mailgun webhooks.

## Step 6: Configure Mailgun Webhook

In your Mailgun dashboard:

1. Go to **Settings** → **Webhooks**
2. Add a new webhook endpoint
3. Set the URL to: `https://yourdomain.com/webhooks/mailgun`
4. Select the events you want to receive (delivered, bounced, complained, etc.)
5. Save the webhook configuration

## Step 7: Create Job Classes (Optional)

If you want to process webhook events with custom logic, create job classes:

```bash
php artisan make:job HandleDeliveredEmail
```

Example job class:

```php
<?php

namespace App\Jobs;

use Spatie\WebhookClient\Models\WebhookCall;

class HandleDeliveredEmail
{
    public function __construct(public WebhookCall $webhookCall)
    {
    }

    public function handle()
    {
        $payload = $this->webhookCall->payload;
        $emailData = $payload['event-data'];

        // Your custom logic here
        // Process the delivered email
    }
}
```

## Step 8: Configure Jobs

Add your job classes to `config/inbounder.php`:

```php
'jobs' => [
    'delivered' => \App\Jobs\HandleDeliveredEmail::class,
    'bounced' => \App\Jobs\HandleBouncedEmail::class,
    'complained' => \App\Jobs\HandleComplainedEmail::class,
],
```

## Step 9: Test the Installation

### Option 1: Use the Test Suite

Run the package tests to ensure everything is working:

```bash
./vendor/bin/phpunit
```

### Option 2: Manual Testing

1. Send a test email through Mailgun
2. Check your Laravel logs for webhook processing
3. Verify the webhook call is stored in the database

## Step 10: Production Considerations

### Security

- Ensure your webhook endpoint uses HTTPS
- Keep your signing secret secure
- Consider rate limiting for webhook endpoints

### Performance

- Configure queue workers for job processing
- Monitor webhook processing performance
- Set up proper logging

### Monitoring

- Monitor webhook delivery success rates
- Set up alerts for webhook failures
- Track job processing times

## Troubleshooting

### Common Issues

1. **Webhook signature validation fails**
   - Verify your signing secret is correct
   - Ensure the webhook URL is accessible
   - Check that Mailgun can reach your endpoint

2. **Jobs not being dispatched**
   - Verify job classes exist and are properly configured
   - Check that queue workers are running
   - Review the webhook payload structure

3. **Database errors**
   - Ensure migrations have been run
   - Check database connection
   - Verify table permissions

### Debug Mode

Enable debug logging in your `.env`:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Getting Help

If you encounter issues:

1. Check the Laravel logs in `storage/logs/laravel.log`
2. Review the webhook call records in the database
3. Open an issue on GitHub with detailed error information

## Next Steps

After installation, you can:

- [Read the full documentation](README.md)
- [Explore the configuration options](README.md#configuration)
- [Learn about webhook events](README.md#webhook-events)
- [Set up event listeners](README.md#listen-to-events)
