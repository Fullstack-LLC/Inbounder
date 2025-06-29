<?php

namespace Fullstack\Inbounder\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Fullstack\Inbounder\InbounderServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseMigrations;

abstract class TestCase extends Orchestra
{
    use DatabaseMigrations;

    protected function getPackageProviders($app)
    {
        return [
            InbounderServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Use SQLite for testing to avoid migration issues
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure cache and session drivers
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('queue.default', 'sync');
    }

    protected function usesDatabase()
    {
        return true;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Run package migrations
        $this->artisan('migrate', [
            '--path' => realpath(__DIR__ . '/../database/migrations'),
            '--realpath' => true,
        ])->run();
    }
}
