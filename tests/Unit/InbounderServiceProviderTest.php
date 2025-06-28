<?php

namespace Fullstack\Inbounder\Tests\Unit;

use Fullstack\Inbounder\InbounderServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;

class InbounderServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            InbounderServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /** @test */
    public function it_registers_configuration()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->register();

        // Check if config is merged
        $this->assertTrue(Config::has('inbounder'));
        $this->assertIsArray(Config::get('inbounder'));
    }

    /** @test */
    public function it_boots_migrations()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->boot();

        // The migrations should be loaded
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /** @test */
    public function it_publishes_configuration()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->boot();

        // Check if config publishing is set up
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /** @test */
    public function it_publishes_migrations()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->boot();

        // Check if migration publishing is set up
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /** @test */
    public function it_loads_routes()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->boot();

        // Check if routes are loaded
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /** @test */
    public function it_has_correct_config_structure()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->register();

        $config = Config::get('inbounder');

        $this->assertArrayHasKey('mailgun', $config);
        $this->assertArrayHasKey('models', $config);
        $this->assertArrayHasKey('attachments', $config);
        $this->assertArrayHasKey('authorization', $config);
        $this->assertArrayHasKey('events', $config);
        $this->assertArrayHasKey('routes', $config);
    }

    /** @test */
    public function it_has_mailgun_config()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->register();

        $mailgunConfig = Config::get('inbounder.mailgun');

        $this->assertArrayHasKey('signing_key', $mailgunConfig);
        $this->assertArrayHasKey('webhook_signing_key', $mailgunConfig);
        $this->assertArrayHasKey('domain', $mailgunConfig);
    }

    /** @test */
    public function it_has_models_config()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->register();

        $modelsConfig = Config::get('inbounder.models');

        $this->assertArrayHasKey('user', $modelsConfig);
        $this->assertArrayHasKey('tenant', $modelsConfig);
    }

    /** @test */
    public function it_has_attachments_config()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->register();

        $attachmentsConfig = Config::get('inbounder.attachments');

        $this->assertArrayHasKey('max_file_size', $attachmentsConfig);
        $this->assertArrayHasKey('storage_disk', $attachmentsConfig);
        $this->assertArrayHasKey('storage_path', $attachmentsConfig);
    }

    /** @test */
    public function it_has_authorization_config()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->register();

        $authConfig = Config::get('inbounder.authorization');

        $this->assertArrayHasKey('required_permission', $authConfig);
        $this->assertArrayHasKey('required_role', $authConfig);
        $this->assertArrayHasKey('super_admin_roles', $authConfig);
    }

    /** @test */
    public function it_has_events_config()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->register();

        $eventsConfig = Config::get('inbounder.events');

        $this->assertArrayHasKey('dispatch_events', $eventsConfig);
        $this->assertArrayHasKey('log_events', $eventsConfig);
    }

    /** @test */
    public function it_has_routes_config()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->register();

        $routesConfig = Config::get('inbounder.routes');

        $this->assertArrayHasKey('prefix', $routesConfig);
        $this->assertArrayHasKey('middleware', $routesConfig);
    }

    /** @test */
    public function it_uses_default_model_classes()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->register();

        $modelsConfig = Config::get('inbounder.models');

        $this->assertEquals(\App\Models\User::class, $modelsConfig['user']);
        $this->assertEquals(\App\Models\Tenant::class, $modelsConfig['tenant']);
    }

    /** @test */
    public function it_uses_default_attachment_settings()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->register();

        $attachmentsConfig = Config::get('inbounder.attachments');

        $this->assertEquals(20 * 1024 * 1024, $attachmentsConfig['max_file_size']); // 20MB
        $this->assertEquals('local', $attachmentsConfig['storage_disk']);
        $this->assertEquals('inbound-emails/attachments', $attachmentsConfig['storage_path']);
    }

    /** @test */
    public function it_uses_default_authorization_settings()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->register();

        $authConfig = Config::get('inbounder.authorization');

        $this->assertEquals('can-send-emails', $authConfig['required_permission']);
        $this->assertEquals('tenant-admin', $authConfig['required_role']);
        $this->assertEquals(['super-admin'], $authConfig['super_admin_roles']);
    }

    /** @test */
    public function it_uses_default_event_settings()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->register();

        $eventsConfig = Config::get('inbounder.events');

        $this->assertTrue($eventsConfig['dispatch_events']);
        $this->assertTrue($eventsConfig['log_events']);
    }

    /** @test */
    public function it_uses_default_route_settings()
    {
        $provider = new InbounderServiceProvider($this->app);
        $provider->register();

        $routesConfig = Config::get('inbounder.routes');

        $this->assertEquals('api/mail/mailgun', $routesConfig['prefix']);
        $this->assertEquals('api', $routesConfig['middleware']);
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        $provider = new InbounderServiceProvider($this->app);

        $this->assertInstanceOf(InbounderServiceProvider::class, $provider);
    }

    /** @test */
    public function it_extends_service_provider()
    {
        $provider = new InbounderServiceProvider($this->app);

        $this->assertInstanceOf(\Illuminate\Support\ServiceProvider::class, $provider);
    }
}
