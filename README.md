# Laravel Inbounder

A Laravel package for handling Mailgun inbound emails with attachments and event-driven processing.

## Features

- 🔐 **Secure Webhook Validation** - Validates Mailgun webhook signatures
- 📧 **Inbound Email Processing** - Handles incoming emails from Mailgun
- 🎯 **Event-Driven Architecture** - Fires Laravel events for different email events
- ⚡ **Job Queue Support** - Processes emails asynchronously via Laravel jobs
- 🔧 **Configurable** - Easy configuration for different webhook endpoints
- 🧪 **Fully Tested** - Comprehensive test coverage

## Installation

You can install the package via composer:

```bash
composer require fullstack/inbounder
```

## Configuration

### 1. Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Fullstack\Inbounder\InbounderServiceProvider" --tag="config"
```

This will create `config/inbounder.php` with the following structure:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mailgun Signing Secret
    |--------------------------------------------------------------------------
    |
    | This is the signing secret from your Mailgun webhook configuration.
    | You can find this in your Mailgun dashboard under Webhooks.
    |
    */
    'signing_secret' => env('MAILGUN_SIGNING_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Job Classes
    |--------------------------------------------------------------------------
    |
    | Here you can configure which job classes should be dispatched for
    | each webhook event type. The event type should be the key and the
    | fully qualified class name should be the value.
    |
    */
    'jobs' => [
        // 'delivered' => \App\Jobs\HandleDeliveredEmail::class,
        // 'bounced' => \App\Jobs\HandleBouncedEmail::class,
        // 'complained' => \App\Jobs\HandleComplainedEmail::class,
    ],
];
```

### 2. Environment Variables

Add the following to your `.env` file:

```env
MAILGUN_SIGNING_SECRET=your_mailgun_signing_secret_here
```

### 3. Database Migration

The package uses the `spatie/laravel-webhook-client` package which requires a migration. Run:

```bash
php artisan vendor:publish --provider="Spatie\WebhookClient\WebhookClientServiceProvider" --tag="migrations"
php artisan migrate
```

## Usage

### 1. Register Routes

The package automatically registers a route macro. You can use it in your `routes/web.php` or `routes/api.php`:

```php
Route::inbounder('webhooks/mailgun');
```

This creates a POST route at `/webhooks/mailgun` that handles incoming Mailgun webhooks.

### 2. Create Job Classes

Create job classes to handle different webhook events:

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

        // Process the delivered email
        $emailData = $payload['event-data'];

        // Your logic here...
    }
}
```

### 3. Configure Jobs

Add your job classes to the configuration:

```php
// config/inbounder.php
'jobs' => [
    'delivered' => \App\Jobs\HandleDeliveredEmail::class,
    'bounced' => \App\Jobs\HandleBouncedEmail::class,
    'complained' => \App\Jobs\HandleComplainedEmail::class,
],
```

### 4. Listen to Events

The package fires Laravel events for each webhook event. You can listen to these events:

```php
// In your EventServiceProvider
protected $listen = [
    'inbounder::delivered' => [
        \App\Listeners\ProcessDeliveredEmail::class,
    ],
    'inbounder::bounced' => [
        \App\Listeners\ProcessBouncedEmail::class,
    ],
];
```

## Webhook Events

The package handles the following Mailgun webhook events:

- `delivered` - Email was successfully delivered
- `bounced` - Email bounced
- `complained` - Recipient marked email as spam
- `unsubscribed` - Recipient unsubscribed
- `opened` - Email was opened
- `clicked` - Link in email was clicked

## Multiple Webhook Endpoints

You can configure multiple webhook endpoints with different signing secrets:

```php
// config/inbounder.php
'signing_secret' => env('MAILGUN_SIGNING_SECRET'),
'signing_secret_secondary' => env('MAILGUN_SIGNING_SECRET_SECONDARY'),
```

Then register routes for each:

```php
Route::inbounder('webhooks/mailgun');
Route::inbounder('webhooks/mailgun/secondary');
```

## Testing

The package includes comprehensive tests. Run them with:

```bash
./vendor/bin/phpunit
```

## Security

The package validates webhook signatures to ensure requests are coming from Mailgun. Make sure to:

1. Keep your signing secret secure
2. Use HTTPS in production
3. Validate webhook signatures (handled automatically)

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

If you encounter any issues or have questions, please open an issue on GitHub.
