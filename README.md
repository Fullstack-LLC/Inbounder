# Inbounder - Laravel Mailgun Inbound Email Handler

A Laravel package for handling Mailgun inbound emails with attachments and event-driven processing.

## Features

- ✅ **Mailgun Webhook Integration** - Handle inbound emails from Mailgun
- ✅ **Signature Verification** - Secure webhook signature validation
- ✅ **Attachment Processing** - Save and manage email attachments
- ✅ **Event-Driven Architecture** - Extensible with Laravel events
- ✅ **User Authorization** - Role and permission-based access control
- ✅ **Duplicate Prevention** - Prevent processing the same email twice
- ✅ **Configurable** - Customizable settings for your needs
- ✅ **Laravel 10/11 Compatible** - Works with latest Laravel versions

## Installation

### Via Composer

```bash
composer require fullstack/inbounder
```

### Manual Installation

1. Clone or download this package
2. Add to your `composer.json`:

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

3. Install the package:

```bash
composer require fullstack/inbounder
```

## Configuration

### 1. Publish Configuration

```bash
php artisan vendor:publish --tag=inbounder-config
```

### 2. Publish Migrations

```bash
php artisan vendor:publish --tag=inbounder-migrations
php artisan migrate
```

### 3. Environment Variables

Add these to your `.env` file:

```env
# Mailgun Configuration
MAILGUN_SIGNING_KEY=your-mailgun-signing-key
MAILGUN_WEBHOOK_SIGNING_KEY=your-webhook-signing-key
MAILGUN_DOMAIN=your-mailgun-domain

# Inbounder Configuration
INBOUNDER_MAX_ATTACHMENT_SIZE=20971520  # 20MB in bytes
INBOUNDER_STORAGE_DISK=local
INBOUNDER_STORAGE_PATH=inbound-emails/attachments
INBOUNDER_REQUIRED_PERMISSION=can-send-emails
INBOUNDER_REQUIRED_ROLE=tenant-admin
INBOUNDER_DISPATCH_EVENTS=true
INBOUNDER_LOG_EVENTS=true
```

## Usage

### 1. Set Up Mailgun Route

In your Mailgun dashboard, create a route that forwards emails to:

```
https://your-domain.com/api/mail/mailgun
```

### 2. User Authorization

Users must have the required permission and role to send emails. The package integrates with Spatie Laravel Permission:

```php
// Give a user permission to send emails
$user->givePermissionTo('can-send-emails', 'tenant-admin');

// Or make them a super admin
$user->assignRole('super-admin');
```

### 3. Listen to Events

The package dispatches events you can listen to:

```php
// In your EventServiceProvider
protected $listen = [
    \Fullstack\Inbounder\Events\InboundEmailReceived::class => [
        \App\Listeners\LogInboundEmail::class,
    ],
    \Fullstack\Inbounder\Events\InboundEmailProcessed::class => [
        \App\Listeners\NotifyEmailProcessed::class,
    ],
    \Fullstack\Inbounder\Events\InboundEmailFailed::class => [
        \App\Listeners\HandleEmailFailure::class,
    ],
];
```

### 4. Access Email Data

```php
use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailAttachment;

// Get all inbound emails
$emails = InboundEmail::with('attachments')->get();

// Get emails from a specific sender
$emails = InboundEmail::where('from_email', 'user@example.com')->get();

// Get attachments for an email
$email = InboundEmail::find(1);
$attachments = $email->attachments;

// Get attachment URL
$attachment = InboundEmailAttachment::find(1);
$url = $attachment->url; // Full URL to the file
$size = $attachment->formatted_size; // "2.5 MB"
```

## Configuration Options

### Mailgun Settings

```php
'mailgun' => [
    'signing_key' => env('MAILGUN_SIGNING_KEY'),
    'webhook_signing_key' => env('MAILGUN_WEBHOOK_SIGNING_KEY'),
    'domain' => env('MAILGUN_DOMAIN'),
],
```

### Attachment Settings

```php
'attachments' => [
    'max_file_size' => env('INBOUNDER_MAX_ATTACHMENT_SIZE', 20 * 1024 * 1024), // 20MB default
    'storage_disk' => env('INBOUNDER_STORAGE_DISK', 'local'),
    'storage_path' => env('INBOUNDER_STORAGE_PATH', 'inbound-emails/attachments'),
],
```

### Authorization Settings

```php
'authorization' => [
    'required_permission' => env('INBOUNDER_REQUIRED_PERMISSION', 'can-send-emails'),
    'required_role' => env('INBOUNDER_REQUIRED_ROLE', 'tenant-admin'),
    'super_admin_roles' => [
        'super-admin',
    ],
],
```

### Event Settings

```php
'events' => [
    'dispatch_events' => env('INBOUNDER_DISPATCH_EVENTS', true),
    'log_events' => env('INBOUNDER_LOG_EVENTS', true),
],
```

## API Endpoints

### POST /api/mail/mailgun

Handles incoming Mailgun webhooks.

**Response Codes:**
- `200` - Email processed successfully
- `401` - Invalid signature
- `406` - Processing failed (validation, authorization, etc.)

**Response Format:**
```json
{
    "message": "Email has been successfully processed",
    "email_id": 123
}
```

## Events

### InboundEmailReceived

Fired when an email is received and validated.

```php
class InboundEmailReceived
{
    public array $emailData;
    public array $attachments;
    public array $requestData;
}
```

### InboundEmailProcessed

Fired when an email is successfully processed and saved.

```php
class InboundEmailProcessed
{
    public InboundEmail $email;
    public array $attachments;
}
```

### InboundEmailFailed

Fired when email processing fails.

```php
class InboundEmailFailed
{
    public array $emailData;
    public string $error;
    public array $requestData;
}
```

## Testing

```bash
# Run package tests
composer test

# Run tests in your Laravel app
php artisan test --filter=InboundMailControllerTest
```

## Security

- **Signature Verification** - All webhooks are verified using Mailgun's signing key
- **Timestamp Validation** - Prevents replay attacks
- **User Authorization** - Only authorized users can send emails
- **File Size Limits** - Configurable attachment size limits
- **Duplicate Prevention** - Prevents processing the same email twice

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

For support, please open an issue on GitHub or contact the Fullstack team.
