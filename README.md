# Inbounder - Laravel Mailgun Integration Package

A comprehensive Laravel package for Mailgun integration with email templates, distribution lists, webhook handling, and queue management.

## Features

- **Email Templates**: Create and manage reusable email templates with variable substitution
- **Distribution Lists**: Manage email distribution lists with subscriber management
- **Webhook Processing**: Handle Mailgun webhooks for tracking and analytics
- **Inbound Email**: Process incoming emails with attachment support
- **Queue Management**: Dedicated queue configuration for email processing
- **Authorization**: Flexible authorization system (Gates, Policies, Spatie Permissions)
- **Event System**: Comprehensive event system for all operations
- **Console Commands**: CLI tools for managing templates and distribution lists

## Installation

```bash
composer require fullstack/inbounder
```

## Configuration

### Basic Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=inbounder-config
```

Configure your Mailgun credentials in `.env`:

```env
MAILGUN_DOMAIN=your-domain.com
MAILGUN_SECRET=your-api-key
MAILGUN_WEBHOOK_SIGNING_KEY=your-webhook-signing-key
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME=Your App Name
```

### Queue Configuration

The package supports custom queue configuration for better performance and isolation:

```env
# Enable custom queue configuration
MAILGUN_QUEUE_ENABLED=true

# Default queue name
MAILGUN_QUEUE_NAME=mailgun

# Specific queue names for different job types
MAILGUN_QUEUE_TEMPLATED_EMAILS=mailgun-emails
MAILGUN_QUEUE_WEBHOOKS=mailgun-webhooks
MAILGUN_QUEUE_INBOUND=mailgun-inbound
MAILGUN_QUEUE_TRACKING=mailgun-tracking

# Queue connection (default, redis, sqs, etc.)
MAILGUN_QUEUE_CONNECTION=redis

# Retry configuration
MAILGUN_QUEUE_MAX_ATTEMPTS=3
MAILGUN_QUEUE_RETRY_DELAY=60
MAILGUN_QUEUE_BACKOFF=true

# Timeout configuration
MAILGUN_QUEUE_JOB_TIMEOUT=300
MAILGUN_QUEUE_TIMEOUT=600

# Batch processing
MAILGUN_QUEUE_BATCH_ENABLED=true
MAILGUN_QUEUE_BATCH_SIZE=100
MAILGUN_QUEUE_BATCH_DELAY=5
```

### Queue Workers

Set up queue workers for the Mailgun queues:

```bash
# Process templated emails
php artisan queue:work --queue=mailgun-emails

# Process webhook events
php artisan queue:work --queue=mailgun-webhooks

# Process inbound emails
php artisan queue:work --queue=mailgun-inbound

# Process tracking events
php artisan queue:work --queue=mailgun-tracking
```

Or use supervisor to manage multiple workers:

```ini
[program:mailgun-emails]
command=php /path/to/your/app/artisan queue:work --queue=mailgun-emails --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/mailgun-emails.log
```

## Usage

### Email Templates

Create an email template:

```php
use Inbounder\Models\EmailTemplate;

$template = EmailTemplate::create([
    'name' => 'Welcome Email',
    'slug' => 'welcome-email',
    'subject' => 'Welcome to {{company}}!',
    'html_content' => '<h1>Welcome {{name}}!</h1><p>Thank you for joining {{company}}.</p>',
    'text_content' => 'Welcome {{name}}! Thank you for joining {{company}}.',
    'variables' => ['name', 'company'],
    'is_active' => true,
]);
```

Send a templated email:

```php
use Inbounder\Services\TemplatedEmailJobDispatcher;

$dispatcher = app(TemplatedEmailJobDispatcher::class);

// Send to one recipient
$dispatcher->sendToOne(
    'user@example.com',
    'welcome-email',
    ['name' => 'John Doe', 'company' => 'Acme Corp']
);

// Send to multiple recipients
$recipients = [
    ['email' => 'user1@example.com', 'name' => 'John', 'company' => 'Acme'],
    ['email' => 'user2@example.com', 'name' => 'Jane', 'company' => 'Acme'],
];

$dispatcher->sendToMany($recipients, 'welcome-email');

// Send as a batch
$batch = $dispatcher->sendBatch($recipients, 'welcome-email');
```

### Distribution Lists

Create a distribution list:

```php
use Inbounder\Models\DistributionList;

$list = DistributionList::create([
    'name' => 'Newsletter Subscribers',
    'slug' => 'newsletter-subscribers',
    'description' => 'Monthly newsletter subscribers',
    'category' => 'newsletter',
    'is_active' => true,
]);
```

Add subscribers:

```php
use Inbounder\Services\DistributionListService;

$service = app(DistributionListService::class);

$service->addSubscribers($list->id, [
    ['email' => 'user1@example.com', 'name' => 'John Doe'],
    ['email' => 'user2@example.com', 'name' => 'Jane Smith'],
]);
```

Send to distribution list:

```php
$service->sendCampaign($list->id, 'newsletter-template', [
    'month' => 'January',
    'year' => '2024',
]);
```

### Webhook Handling

Set up webhook routes in your `routes/web.php`:

```php
Route::post('/mailgun/webhook', [MailgunController::class, 'webhook'])
    ->middleware('verify.mailgun.webhook');

Route::post('/mailgun/inbound', [MailgunController::class, 'inbound'])
    ->middleware('verify.mailgun.webhook');
```

### Authorization

Configure authorization in your `config/inbounder.php`:

```php
'authorization' => [
    'method' => 'gate', // 'gate', 'policy', or 'spatie'
    'gate_name' => 'send-email',
    'policy_method' => 'sendEmail',
    'spatie_permission' => 'send email',
],
```

Define your authorization logic:

```php
// Using Gates
Gate::define('send-email', function ($user) {
    return $user->hasPermission('send-email');
});

// Using Policies
class UserPolicy
{
    public function sendEmail(User $user): bool
    {
        return $user->hasPermission('send-email');
    }
}

// Using Spatie Permissions
$user->givePermissionTo('send email');
```

### Events

Listen to package events:

```php
use Inbounder\Events\EmailTemplateCreated;
use Inbounder\Events\DistributionListCreated;

Event::listen(EmailTemplateCreated::class, function ($event) {
    Log::info('Email template created', [
        'template_id' => $event->getTemplateId(),
        'template_name' => $event->getTemplateName(),
    ]);
});

Event::listen(DistributionListCreated::class, function ($event) {
    Log::info('Distribution list created', [
        'list_id' => $event->getListId(),
        'list_name' => $event->getListName(),
    ]);
});
```

## Console Commands

### Email Templates

```bash
# Create a template
php artisan inbounder:templates:create

# List templates
php artisan inbounder:templates:list

# Send a templated email
php artisan inbounder:templates:send
```

### Distribution Lists

```bash
# Create a distribution list
php artisan inbounder:lists:create

# List distribution lists
php artisan inbounder:lists:list

# Add subscribers
php artisan inbounder:lists:add-subscribers

# Remove subscribers
php artisan inbounder:lists:remove-subscribers

# Send campaign
php artisan inbounder:lists:send-campaign
```

## Testing

Run the test suite:

```bash
composer test
```

The package includes comprehensive tests with high coverage for all components.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
