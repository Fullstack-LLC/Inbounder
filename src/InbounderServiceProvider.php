<?php

namespace Fullstack\Inbounder;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InbounderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/inbounder.php',
            'inbounder'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/inbounder.php' => config_path('inbounder.php'),
        ], 'inbounder-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'inbounder-migrations');

        $this->loadRoutes();
    }

    /**
     * Load package routes.
     */
    protected function loadRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/mail/mailgun')
            ->group(__DIR__.'/../routes/api.php');
    }
}
