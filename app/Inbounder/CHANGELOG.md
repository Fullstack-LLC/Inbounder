# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release of Inbounder package
- Email template management with variable substitution
- Distribution list management with subscriber handling
- Mailgun webhook processing and event storage
- Inbound email processing with attachment support
- Queue management with configurable queues for different job types
- Authorization system supporting Gates, Policies, and Spatie Permissions
- Comprehensive event system for all operations
- Console commands for template and distribution list management
- High test coverage with PHPUnit and Pest
- Queue service for managing email processing queues
- Batch processing support for large email campaigns

### Features
- **Email Templates**: Create, update, and manage reusable email templates
- **Distribution Lists**: Manage subscribers and send campaigns
- **Webhook Processing**: Handle Mailgun webhooks for tracking and analytics
- **Queue Management**: Dedicated queues for different email processing tasks
- **Authorization**: Flexible authorization using Laravel's authorization system
- **Events**: Comprehensive event system for monitoring and logging
- **Console Commands**: CLI tools for managing all aspects of the package

### Technical
- Laravel 10+ compatibility
- PHP 8.2+ requirement
- Comprehensive test suite with 90%+ coverage
- PSR-4 autoloading
- Laravel service provider integration
- Migration support for database tables
