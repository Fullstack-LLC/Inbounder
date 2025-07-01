<?php

namespace Inbounder\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Set up default Mailgun configuration for tests
        config([
            'services.mailgun' => [
                'domain' => 'test.example.com',
                'secret' => 'test-secret',
                'endpoint' => 'https://api.mailgun.net',
                'webhook_signing_key' => 'test-signing-key',
            ],
            'mailgun' => [
                'webhook_signing_key' => 'test-signing-key',
                'domain' => 'test.example.com',
                'secret' => 'test-secret',
                'endpoint' => 'api.mailgun.net',
                'user_model' => \Inbounder\Tests\Stubs\User::class,
                'authorization' => [
                    'method' => 'none',
                ],
                'database' => [
                    'webhooks' => [
                        'enabled' => false,
                        'model' => \Inbounder\Models\MailgunEvent::class,
                    ],
                    'inbound' => [
                        'enabled' => false,
                        'model' => \Inbounder\Models\MailgunInboundEmail::class,
                    ],
                    'outbound' => [
                        'enabled' => false,
                        'model' => \Inbounder\Models\MailgunOutboundEmail::class,
                    ],
                ],
                'outbound' => [
                    'enabled' => true,
                    'default_from' => [
                        'address' => 'test@example.com',
                        'name' => 'Test Sender',
                    ],
                    'tracking' => [
                        'opens' => true,
                        'clicks' => true,
                        'unsubscribes' => true,
                    ],
                ],
                'queue' => [
                    'enabled' => false,
                ],
            ],
        ]);

        // Manually boot the MailgunServiceProvider to ensure mailer configuration
        $provider = new \Inbounder\Providers\MailgunServiceProvider($this->app);
        $provider->boot();

        // Ensure migrations are run
        //$this->artisan('migrate:fresh');
    }

    protected function getPackageProviders($app)
    {
        return [
            \Illuminate\Events\EventServiceProvider::class,
            \Inbounder\Providers\MailgunServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../src/database/migrations');
    }

    protected function getEnvironmentSetUp($app)
    {
        // Configure database for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
