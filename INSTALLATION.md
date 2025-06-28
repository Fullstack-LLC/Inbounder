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
# Mailgun Configuration
MAILGUN_SIGNING_KEY=your-mailgun-signing-key
MAILGUN_WEBHOOK_SIGNING_KEY=your-webhook-signing-key
MAILGUN_DOMAIN=your-mailgun-domain

# Inbounder Configuration
INBOUNDER_MAX_ATTACHMENT_SIZE=20971520
INBOUNDER_STORAGE_DISK=local
INBOUNDER_STORAGE_PATH=inbound-emails/attachments
INBOUNDER_REQUIRED_PERMISSION=can-send-emails
INBOUNDER_REQUIRED_ROLE=tenant-admin
INBOUNDER_DISPATCH_EVENTS=true
INBOUNDER_LOG_EVENTS=true
```

## Step 6: Test the Installation

The package will automatically register the route `/api/mail/mailgun` for handling Mailgun webhooks.

You can test it by sending an email to your Mailgun domain and checking if it's processed correctly.

## Development

To make changes to the package:

1. Edit files in `packages/redbird/inbounder/src/`
2. Run `composer dump-autoload` to reload changes
3. Test your changes

## Publishing to Packagist

When ready to publish:

1. Create a GitHub repository
2. Push the package code
3. Register on Packagist.org
4. Update the `composer.json` to point to the public repository
