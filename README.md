# Inbounder - Laravel Mailgun Inbound Email Handler

A Laravel package for handling Mailgun inbound emails with attachments and event-driven processing. Perfect for building support ticketing systems, document processing workflows, and email archiving solutions.

## Features

- ✅ **Mailgun Webhook Integration** - Handle inbound emails from Mailgun with proper webhook compliance
- ✅ **Signature Verification** - Secure webhook signature validation with timestamp checking
- ✅ **Attachment Processing** - Save and manage email attachments with size limits and organized storage
- ✅ **Event-Driven Architecture** - Extensible with Laravel events for custom processing
- ✅ **User Authorization** - Role and permission-based access control with Spatie Laravel Permission
- ✅ **Duplicate Prevention** - Prevent processing the same email twice using Message-ID
- ✅ **Configurable Models** - Use your own User and Tenant models
- ✅ **Multi-tenant Support** - Built-in tenant isolation and domain-based routing
- ✅ **Comprehensive Testing** - Full test coverage with Pest and Testbench
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

### 2. Configure Custom Models (Optional)

If you want to use your own User and Tenant models, update the config:

```php
// config/inbounder.php
'models' => [
    'user' => \App\Models\User::class,
    'tenant' => \App\Models\Tenant::class,
],
```

### 3. User Authorization

Users must have the required permission and role to send emails. The package integrates with Spatie Laravel Permission:

```php
// Give a user permission to send emails
$user->givePermissionTo('can-send-emails', 'tenant-admin');

// Or make them a super admin
$user->assignRole('super-admin');
```

### 4. Listen to Events

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

### 5. Access Email Data

```php
use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailAttachment;

// Get all inbound emails
$emails = InboundEmail::with('attachments')->get();

// Get emails from a specific sender
$emails = InboundEmail::where('from_email', 'user@example.com')->get();

// Get emails for a specific tenant
$emails = InboundEmail::where('tenant_id', 1)->get();

// Get attachments for an email
$email = InboundEmail::find(1);
$attachments = $email->attachments;

// Get attachment URL and formatted size
$attachment = InboundEmailAttachment::find(1);
$url = $attachment->url; // Full URL to the file
$size = $attachment->formatted_size; // "2.5 MB"

// Filter attachments by size or content type
$largeAttachments = InboundEmailAttachment::where('size', '>', 1024 * 1024)->get();
$pdfAttachments = InboundEmailAttachment::where('content_type', 'application/pdf')->get();
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

### Model Settings

```php
'models' => [
    'user' => env('INBOUNDER_USER_MODEL', \App\Models\User::class),
    'tenant' => env('INBOUNDER_TENANT_MODEL', \App\Models\Tenant::class),
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

Handles incoming Mailgun webhooks with full Mailgun compliance.

**Response Codes (Mailgun Compliant):**
- `200` - Email processed successfully (Mailgun will not retry)
- `406` - Processing failed or rejected (Mailgun will not retry)
- Any other code - Mailgun will retry the webhook

**Response Format:**
```json
{
    "message": "Email has been successfully processed",
    "email_id": 123
}
```

**Error Response:**
```json
{
    "error": "Signature is invalid."
}
```

## Events

### InboundEmailReceived

Fired when an email is received and validated, before processing.

```php
class InboundEmailReceived
{
    public array $emailData;      // Email metadata
    public array $attachments;    // Attachment information
    public array $requestData;    // Raw request data
}
```

### InboundEmailProcessed

Fired when an email is successfully processed and saved to the database.

```php
class InboundEmailProcessed
{
    public InboundEmail $email;   // The saved email model
    public array $attachments;    // Processed attachment information
}
```

### InboundEmailFailed

Fired when email processing fails for any reason.

```php
class InboundEmailFailed
{
    public array $emailData;      // Available email data
    public string $error;         // Error message
    public array $requestData;    // Raw request data
}
```

## Database Schema

### InboundEmail Model

```php
// Main email record
InboundEmail::create([
    'message_id' => '<unique@example.com>',
    'from_email' => 'sender@example.com',
    'from_name' => 'John Doe',
    'to_email' => 'recipient@example.com',
    'to_name' => 'Jane Smith',
    'subject' => 'Test Email',
    'body_plain' => 'Plain text content',
    'body_html' => '<p>HTML content</p>',
    'stripped_text' => 'Stripped text content',
    'stripped_html' => '<p>Stripped HTML</p>',
    'stripped_signature' => 'Email signature',
    'recipient_count' => 1,
    'timestamp' => Carbon::now(),
    'token' => 'webhook-token',
    'signature' => 'webhook-signature',
    'domain' => 'example.com',
    'message_headers' => [['From', 'sender@example.com']],
    'envelope' => ['from' => 'sender@example.com', 'to' => 'recipient@example.com'],
    'attachments_count' => 2,
    'size' => 1024000,
    'sender_id' => 1,
    'tenant_id' => 1,
]);
```

### InboundEmailAttachment Model

```php
// Attachment record
InboundEmailAttachment::create([
    'inbound_email_id' => 1,
    'filename' => 'document.pdf',
    'content_type' => 'application/pdf',
    'size' => 1024000,
    'file_path' => 'inbound-emails/attachments/2024/01/15/abc123_document.pdf',
    'original_name' => 'document.pdf',
    'disposition' => 'attachment',
]);
```

## Testing

```bash
# Run package tests
composer test

# Run tests with coverage
./vendor/bin/pest --coverage

# Run specific test file
./vendor/bin/pest tests/Feature/InboundMailControllerTest.php
```

## Security

- **Signature Verification** - All webhooks are verified using Mailgun's signing key
- **Timestamp Validation** - Prevents replay attacks (5-minute window)
- **User Authorization** - Only authorized users can send emails
- **File Size Limits** - Configurable attachment size limits (20MB default)
- **Duplicate Prevention** - Prevents processing the same email twice using Message-ID
- **Tenant Isolation** - Multi-tenant support with proper data separation
- **Input Validation** - Comprehensive validation of all incoming data

## Use Cases

This package is perfect for:

- **Support Ticketing Systems** - Convert emails to tickets with attachments
- **Document Processing Workflows** - Process email attachments automatically
- **Email Archiving** - Store all inbound emails with attachments
- **Multi-tenant SaaS** - Handle emails for multiple tenants
- **Customer Service** - Route emails to appropriate agents
- **Compliance & Auditing** - Maintain email records for compliance

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Ensure all tests pass
6. Submit a pull request

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

For support, please open an issue on GitHub or contact the Fullstack team.
